<?php
require_once(__DIR__ . '/../core/route.php');

#Route::import(__DIR__ . '/../conf.php');

$app = Route::g()->init();

$app->library('bot');
$app->library('formity2', 'formity');
$app->libraryOwn('curly');

$re = Formity::getInstance('persona');
$re->addField('nombres', 'input:text');
$re->addField('sexo', 'select')->setOptions(array('1' => 'Hombre', '2' => 'Mujer'));
$re->addField('edad', 'input:text');

$app->any('webhook', function() {
  Bot::config(__DIR__ . '/fileMind.json');

  Bot::registerFormity('persona', function($queue, $re) {
    $queue->reply(json_encode($re->getData()));
    $queue->goQueue(1);
  }, function($queue) {
    $queue->reply("Se ha cancelado el formulario");
    $queue->goQueue(1);
  });

  $queue = Bot::createQueue(1);

  $queue->hears('iniciar', function ($queue) {
    $queue->reply("Iniciamos el formulario:");
    $queue->replyFormity('persona');
  });
  $queue->hears('opciones', function ($queue) {
    $queue->reply("mis opciones");
  });
  $queue->hears(function($n) {
    return strpos($n->text, 'hola') !== false;
  }, function($queue) {
    $queue->reply('Hola! ¿En qué podemos ayudarte?');
  });

  $message = json_decode(file_get_contents("php://input"), true);
  $msg = new class {
    public $chat_id;
    public $text;
    public function reply($txt) {
      $token = '1247324805:AAFFOlNSgpPCWS2D-GmtyattJe0a3oGZ9yQ';
      $url   = 'https://api.telegram.org/bot' . $token . '/sendMessage';
      echo "Respondiendo: " . $txt;
      Curly(CURLY_GET, $url, null, array(
        'chat_id' => $this->chat_id,
        'text'    => $txt,
      ));
    }
  };
  $msg->chat_id = $message['message']['chat']['id'];
  $msg->text  = $message['message']['text'];
  Bot::listen(1, $msg);

  Formity::delete('persona');

})->else(function() {
  Route::response(404);
});
