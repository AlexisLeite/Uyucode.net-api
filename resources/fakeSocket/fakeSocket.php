<?php

/**
 * The fakesocket is a class which allows the user to establish a connection between multiple hosts and a server, in order to share all kind of information, which could be put in a json format. The information you share through the fake socket is not important, its purpose is to give tools to make the connection and keep it alive as long as the server app considers it necessary. 
 *  
 * @author Alexis Leite
 * @license MIT
 */
class FakeSocket
{
  private $options = [
    'keepAlive' => true,
    'keepAliveTime' => 1,
    'keepAliveTolerance' => 10,
    'mantainFrecuency' => 20,
    'revealClients' => true
  ];

  private $action,
    $clients,
    $currentClient,
    $hash = null,
    $receivedMessages = [],
    $registerData,
    $returnObject = ['status' => 'ok'];

  /**
   * ## Usage
   */

  /**
   * The socket will behave as a tool to be used by a server Application (from now on: the server), which will offer a service to its clients. It doesn't worry about the content of the service but about the rules of the connections received. To guarantee an stable connection, it has some rules that must be followed.
   * 
   *  In order to create a socket in the server, you must instance the class through the new FakeSocket($options) constructor
   * 
   * @param array $options The *optional* options parameter, if given might be an associative array containing the configurations you want to set. Those configurations are:
   * 
   * - bool **keepAlive**: When enabled, the server will close those connections which don't update their state, and will consider them as lost. This gives you the opportunity to know which clients are online and which are not.
   * - number **keepAliveTime**: This time is set in seconds and is the time which must pass from the moment the client receives the answer from the server and when it sends back the keepAlive package.
   * - number **keepAliveTolerance**: This time is set in seconds and is a tolerance to consider the connection time. Ifit's set too short, there it will be probably accidental disconnections.
   * - number **mantainFrecuency**: This time is set in seconds, the server will execute each given time in seconds a mantainance to delete the old information from the records, in order to keep the server fluid.
   * - bool **revealClients**: If enabled, the server will send to the clients a list and the correspondent updates about the status of the clients. This way, they will be able to mantain an updated list of online clients on every moment.
   * 
   * The default options are:
   * 
   * ```json
   * [
   *    'keepAlive' => true,
   *    'keepAliveTime' => 5,
   *    'keepAliveTolerance' => 10,
   *    'mantainFrecuency' => 20,
   *    'revealClients' => true
   * ];
   * ```
   * 
   * @throws OutOfBoundsException when the passed array has incorrect keys in order to prevent grammar errors.
   * 
   * @example
   * 
   * ```php
   * 
   *  $socket = new Socket([
   *    'keepAlive' => true, // Enable the keepAlive functionality
   *    'keepAliveTime' => 1, // Expect the keepAlive package to be sent each 1 second
   *    'keepAliveTolerance' => 8, // Give a tolerance of 8 seconds on each keepAlive
   *    'revealClients' => true // Send to the clients a list of all the online clients
   *  ]);
   * 
   * ```
   */
  public function __construct($options = [])
  {
    // Check for the options to be correct
    foreach ($options as $key => $val) {
      if (!array_key_exists($key, $this->options))
        throw new OutOfBoundsException("The options set in the socket are not correct.");
    }

    // Check the correctness of the protocol
    $correctProtocol = false;

    /**
     * ## The protocol
     * 
     * In order to process correctly the information received, the socket accepts a strict set of rules that must match in order to continue with the comunication. In other words, if these rules are not matched, the server closes the connection.
     * 
     * There are various scenarios which the socket handle, and are described here:
     * 
     * ### The first connection
     * 
     * When the client wants to establish a connection, it must declare it as the socket must ask for permission to the server. The implementation is discused in @onConnectionRequest. To do so, it must send the following package:
     * 
     * ```json
     * [
     * "action": "register",
     * "registerData": {} // The contents of the registerData object is not of matter of the socket, it will just pass it to the server and depending on its answer, will allow or deny the connection.
     * ]
     * ```
     * 
     * ### Sending messages to the server
     * 
     * Each time the client must send a message to the server, it must use the following format:
     * 
     * ```json
     * [
     * "action": "post",
     * "messages": [{}, {}, {}] // Each message can be either an array, an object, an string or wathever. As with the registerData, the socket doesn't worry about its contents. Its only mission is to deliver it to the server. The implementation is described in @onMessage.
     * "hash": "..." // The hash is privided by the server as an identification, it must be sent with all the requests in order to the socket to accept it
     * ]
     * ```
     * 
     * ### Just keeping alive
     * 
     * If there is no need to send messages, but the client does not want to lose the connection, it can send the following package:
     * 
     * ```json
     * [ "action": "keepAlive", "hash": "..." ]
     * ```
     * 
     * ### Disconnecting
     * 
     * It is a good practice to disconnect when the service won't be used anymore. Despite the fact that te server will close the connections automatically, if you close the unused connections, it will work faster.
     * 
     * ```json
     * [ "action": "disconnect", "hash": "..." ]
     * ```
     * 
     * ### Requests and answers
     * 
     * Each time you send a request it will give and answer, it will depend on the context and the request. The following table is a quick guide about the possible answers:
     * 
     * | Request action and  properties           | Possible answer status | Possible answer properties                 |
     * | ---------------------------------------- | ---------------------- | ------------------------------------------ |
     * | action:"register", registerData: {...}   | ok                     | hash, registerData, clientsList, keepAlive |
     * | action:"post", messages:[...], hash: ... | ok                     | keepAlive, clientsList, messages           |
     * | action:"keepAlive", hash: ...            | ok                     | keepAlive, clientsList, messages           |
     * | action:"disconnect", hash: ...           | connectionEnd          |                                            |
     * | any request                              | error                  | message, title                             |
     * 
     */
    if (isset($_POST['action'])) {
      $this->action = $_POST['action'];
      $acceptedArguments = [
        [
          'action' => 'register',
          'required' => ['registerData']
        ],
        [
          'action' => 'post',
          'required' => ['messages', 'hash']
        ],
        [
          'action' => 'keepAlive',
          'required' => ['hash']
        ],
        [
          'action' => 'disconnect',
          'required' => ['hash']
        ]
      ];

      foreach ($acceptedArguments as $acceptedArgument) {
        $required = [];
        foreach ($acceptedArgument['required'] as $requiredKey) {
          $required[$requiredKey] = true;
        }

        if (
          $this->action === $acceptedArgument['action'] && count(array_intersect_key($_POST, $required)) === count($required)
        ) {
          $correctProtocol = true;
          break;
        }
      }
    }
    if (!$correctProtocol) {
      $this->error('Protocol error', 'See the documentation to resolve this error.');
      return;
    }

    // Load the resource
    $this->broadcasts = resource('fakeSocket/broadcasts');
    $this->clients = resource('fakeSocket/clients');
    $this->clientsUpdates = resource('fakeSocket/clientsUpdates');

    // Set the passed options
    $this->options = array_merge($this->options, $options);
    $this->checkMantain();


    // Start to work
    $this->parse();
  }

  // The destructor will be used to save the changes on the clients
  public function __destruct()
  {
    if ($this->hash !== null && $this->clients->exists($this->hash)) {
      $this->clients->update($this->hash, ['nextUpdate' => time() + $this->options['keepAliveTime'] + $this->options['keepAliveTolerance']])->save();
    } else if ($_POST['action'] === "disconnect") {
      $this->clients->save();
    }
    if ($this->options['revealClients'])
      $this->clientsUpdates->save();
    $this->broadcasts->save();
  }

  /** 
   * ## Events
   */

  /**
   * Each time a client sends a register request, this event will be fired. In order to accept or reject that request, a callback must be passed. If no callback received, every connection will be rejected. The connection will be accepted only if this callback returns true and rejected in any other case.
   * 
   * @param function callback($registerData) a function which accepts the registerData object and returns a bool
   * 
   * @example
   * 
   * ```php
   * $socket = new FakeSocket();
   * $socket->onConnectionRequest(function(&$registerData) use ($socket) {
   *  if(!$this->isRegistered($registerData['name'])) {
   *    $socket->setDenyReason('Unregistered name');
   *    return false;
   *  } else return true;
   * });
   * ```
   * 
   * @important It is highly recomended to delete the information you consider private on the registerData array through accepting that array as a reference. This way you can keep it private, otherwise it will be sent to all the clients if revealClients is enabled.
   * 
   * @see setDenyReason
   */
  public function onConnectionRequest($callback)
  {
    if ($this->action === 'register') {
      if ($callback($this->registerData)) {
        $this->acceptClient();
      } else {
        $reason = array_key_exists('message', $this->returnObject)
          ? $this->returnObject['message']
          : "The server has rejected your request";
        $this->denyClient("reject", $reason);
      }
    }
  }

  /**
   * Each time a client sends a message, it will be sent to the server through this event. What to do with the messages is pure responsibility of the server.
   * 
   * @param function callback($emitterHash, $message) a function which accepts the message
   * 
   * @example
   * 
   * ```php
   * $socket = new FakeSocket();
   * $socket->onMessage(function($emitter, $message) use ($socket) {
   *  if($this->validate($emitter, $message)) {
   *    $socket->send($message); // Broadcast it back to all clients
   *  }
   * });
   * ```
   * 
   * @see send
   */
  public function onMessage($callback)
  {
    if (!$this->error()) {
      foreach ($this->receivedMessages as $message) {
        $callback($this->hash, $message);
      }
    }
  }

  /** 
   * ## Regular methods
   */

  /**
   * This methods returns a list of all online clients on the moment it's called. 
   * 
   * @return Array each element of the array is an associative array with the following properties: hash, registerData, status
   */
  public function online()
  {
    $currentClients = [];
    $this->clients->each(function ($value, $key) use (&$currentClients) {
      if (array_key_exists('nextUpdate', $value) && $value['nextUpdate'] >= time())
        $currentClients[] = [
          'hash' => $key,
          'registerData' => $value['registerData'],
          'status' => 'connect'
        ];
    });
    return $currentClients;
  }

  /**
   * This method is mandatory to be called, if it's not the socket won't work at all as it wont send any answer to the client.
   * 
   * @example
   * 
   * ```php
   * $socket = new FakeSocket();
   * $socket->onConnectionRequest(function($registerData) use ($socket) { ... });
   * $socket->onMessage(function($message) use ($socket) { ... });
   * $socket->print();
   * ```
   * 
   * @return Array This associative array can be used in any way the server considers appropriate but it was thought as to be used in a json_encode echo.
   */
  public function print()
  {
    if ($this->returnObject['status'] === 'ok') {
      $this->pushReturn([
        'keepAlive' => $this->options['keepAliveTime']
      ]);

      // If an error message is filtered, it must be deleted
      unset($this->returnObject['errorMessage']);

      // Print the clients list updates
      if ($this->options['revealClients'] && !in_array($_POST['action'], ['register', 'disconnect'])) {
        $updatesList = [];

        $lastUpdateReceived = $this->clients->get($this->hash)['lastClientsUpdateReceived'];

        $this->clientsUpdates->each(function ($record) use ($lastUpdateReceived, &$updatesList) {
          if ($record['id'] > $lastUpdateReceived) {
            $updateRecord = $record;
            $updatesList[] = $updateRecord;
          }
        });

        if (count($updatesList)) {
          $this->pushReturnRecursive([
            'clientsList' => $updatesList
          ]);

          $this->clients->update($this->hash, [
            'lastClientsUpdateReceived' => $this->clientsUpdates->lastInsertedKey()
          ]);
        }

        // Print the unread messages
        $unreadMessages = $this->clients->get($this->hash)['unreadMessages'];
        $this->clients->update($this->hash, [
          'unreadMessages' => []
        ]);

        $lastBroadcastReceived = $this->clients->get($this->hash)['lastBroadcastReceived'];
        foreach ($this->broadcasts->all() as $broadcast) {
          if ($broadcast['id'] > $lastBroadcastReceived) {
            $unreadMessages[] = $broadcast['message'];
          }
        }

        $this->clients->update($this->hash, [
          'lastBroadcastReceived' => $this->broadcasts->lastInsertedKey()
        ]);

        if (count($unreadMessages)) {
          $this->pushReturn(['messages' => $unreadMessages]);
        }
      }
    }
    // Print the keep alive time

    // Log the requests
    if (in_array($this->action, [''])) {
      $this->logRequest();
    }

    return $this->returnObject;
  }

  /**
   * This method is the one who sends messages to the clients. It allows to broadcast some information or to send to a particular client. 
   * 
   * @param any $message The object which will be sent to the client, it must be capable of being parsed to json
   * 
   * @param string $receipt It's the hash of the client to who you want to send the message. The client's hashes are provided through the public method @see online or through the clientsList property sent to the clients when the revealClients option is enabled.
   * 
   * @example
   * 
   * ```php
   * $socket = new FakeSocket();
   * ... // Handle register requests
   * $socket->send("Important: the server will add some extra features!"); // Broadcasts a message
   * $socket->onMessage(function($emitter, $message) use ($socket) {
   *  $socket->send("We received your message", $emitter); // Answer the message
   * });
   * ```
   * 
   * @see online
   */
  public function send($message, $receipt = null)
  {
    if ($receipt === null)
      $this->broadcast($message);
    else {
      if ($this->clients->exists($receipt))
        $this->clients->put($receipt, [
          'unreadMessages' => [
            [
              'from' => $this->hash,
              'message' => $message,
              'kind' => 'private'
            ]
          ]
        ]);
    }
  }


  /**
   * When the server receives a connection and it does not pass the validation, that request will be denied. In order to inform the client, the server can call this method and set the reason.
   * 
   * @param any $reason: It can be anything which could be parsed to json
   * 
   * @example
   * 
   * ```php
   * $socket = new FakeSocket();
   * $socket->onConnectionRequest(function($registerData) use ($socket) {
   *  if(!$this->isRegistered($registerData['name'])) {
   *    $socket->setDenyReason('Unregistered name');
   *    return false;
   *  }
   * });
   * ```
   * 
   * @see onConnectionRequest
   */
  public function setDenyReason($reason)
  {
    $this->pushReturn(['message' => $reason]);
  }

  // Private methods

  private function acceptClient()
  {
    unset($this->returnObject['denyReason']);

    // Create a new hash
    do {
      $this->hash = randomHash();
    } while ($this->clients->exists($this->hash));

    // Send the successful result
    $this->pushReturn([
      'status' => 'ok',
      'hash' => $this->hash,
      'registerData' => $_POST['registerData']
    ]);

    // Send the current clients list to the client
    if ($this->options['revealClients']) {
      $currentClients = $this->online();

      if (count($currentClients)) {
        $this->pushReturn([
          'clientsList' => $currentClients
        ]);
      }
    }

    // Initialize the client's data
    $this->clients->add($this->hash, [
      'registerData' => $this->registerData,
      'lastClientsUpdateReceived' => -1,
      'lastBroadcastReceived' => -1,
      'unreadMessages' => []
    ]);

    // Broadcast its connection
    $this->clientUpdate($this->hash, 'connect', [
      'registerData' => $this->registerData
    ]);
  }

  private function broadcast($message)
  {
    $this->broadcasts->add(null, [
      'liveUntil' => time() + $this->options['mantainFrecuency'],
      'message' => [
        'from' => $this->hash,
        'message' => $message,
        'kind' => 'broadcast'
      ]
    ]);
  }

  private function checkMantain()
  {
    $this->mantainResource = resource('fakeSocket/mantain');
    if (!$this->mantainResource->exists('register') || $this->mantainResource->get('register')['nextMantain'] < time()) {
      $this->doMantain();
    }
  }

  private function clientUpdate($hash, $status, $aditionalData = [])
  {
    $this->clientsUpdates->add(null, array_merge($aditionalData, [
      'hash' => $hash,
      'status' => $status,
      'liveUntil' => time() + $this->options['mantainFrecuency']
    ]));
  }

  private function denyClient($title, $reason)
  {
    $this->error($title, $reason);
  }

  private function disconnectClient($hash)
  {
    $this->clients->delete($hash);
    $this->clientUpdate($hash, 'disconnect');
  }

  private function doMantain()
  {
    // Do mantain to the clients
    $this->clients->each(function ($client, $hash) {
      if (!array_key_exists('nextUpdate', $client))
        print_r($client);
      if ($client['nextUpdate'] < time())
        $this->disconnectClient($hash);
    });
    $this->clients->filter(function ($client, $hash) {
      return $client['nextUpdate'] >= time();
    });

    // Do mantain to the clients updates records
    $this->clientsUpdates->filter(function ($record, $id) {
      return time() < $record['liveUntil'];
    });

    // Do mantain the broadcasts
    $this->broadcasts->filter(function ($record, $id) {
      return time() < $record['liveUntil'];
    });

    $this->pushReturn(['mantained' => true]);

    // Update the mantain resource
    $this->mantainResource->update('register', ['nextMantain' => time() + $this->options['mantainFrecuency']])->save();
  }

  // Tells to the socket wether it ocurred an error or not if no argument is passed. Sets the error if the argument is passed
  private function error($title = null, $message = null)
  {
    if ($message === null && $title === null)
      return $this->returnObject['status'] !== 'ok';
    else if ($message === null || $title === null) {
      throw new OutOfBoundsException("The error method is being used incorrectly");
    } else {
      $this->returnObject['status'] = 'error';
      $this->returnObject['title'] = $title;
      $this->returnObject['message'] = $message;
    }
    $this->logRequest();
  }

  private function logRequest()
  {
    $logs = resource('fakeSocket/logs');
    $logs->add(null, ["+++++++++++++++++++++++++++++++++++++++++++++++++++++"]);
    $logs->add(null, [
      'action' => $this->action,
      'hash' => isset($_POST['hash']) ? $_POST['hash'] : 'no',
      'request' => $_POST,
      'returnObject' => $this->returnObject,
      'status' => $this->returnObject['status']
    ])->save();
  }

  // The parse function will define what path the socket must follow to process the request correctly
  private function parse()
  {
    switch ($this->action) {
      case 'register':
        // Store the register data in order to use it within the onConnectionRequest event
        $this->registerData = $_POST['registerData'];
        $this->denyClient("Server error", "There is no evaluation callback set.");
        break;

      case 'disconnect':
        $this->disconnectClient($_POST['hash']);
        $this->pushReturn(['status' => 'connectionEnd']);
        break;

      case 'post':
        $this->receivedMessages = $_POST['messages'];
      case 'keepAlive':
        $this->hash = $_POST['hash'];
        $this->currentClient = $this->clients->get($this->hash);
        if (!$this->currentClient || $this->currentClient['nextUpdate'] < time()) {
          $this->disconnectClient($this->hash);
          return $this->denyClient('timeout', 'Timeout, login again please');
        }

        $this->pushReturn(['status' => 'ok']);
        // Store the messages in order to use them within the onMessage event
        break;
    }
  }

  private function pushReturn($data)
  {
    $this->returnObject = array_merge($this->returnObject, $data);
  }

  private function pushReturnRecursive($data)
  {
    $this->returnObject = array_merge_recursive($this->returnObject, $data);
  }
}
