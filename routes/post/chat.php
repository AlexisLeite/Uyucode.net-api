<?php
return [
  'chat' => function () {
    include(baseDir('resources/fakeSocket/fakeSocket.php'));

    try {
      $socket = new FakeSocket(['keepAliveTime' => 1]);
      $socket->onConnectionRequest(function ($registerData) use ($socket) {
        if (count(array_filter($socket->online(), function ($el) use ($registerData) {
          return $el['registerData']['name'] === $registerData['name'];
        }))) {
          $socket->setDenyReason("The name is already in use");
          return false;
        }
        return true;
      });
      $socket->onMessage(function ($emitter, $message) use ($socket) {
        // This method will return either a receipt or null
        $receipt = exists($message, 'to');

        // IF the receipt is null, the server will broadcast
        $socket->send($message, $receipt);

        // If the receipt is not null, it will send the message back to the origin too
        if ($receipt)
          $socket->send($message, $message['from']);
      });
      $res = $socket->print();
      return $res;
    } catch (Exception $e) {
      return error("Socket error", $e->getMessage());
    }
  },
];
