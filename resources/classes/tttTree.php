<?php

include(__DIR__ . '/tttNode.php');

class tttTree
{
  private function check($squares, $name)
  {
    $rows = [
      [0, 1, 2],
      [0, 4, 8],
      [0, 3, 6],
      [3, 4, 5],
      [6, 4, 2],
      [6, 7, 8],
      [1, 4, 7],
      [2, 5, 8],
    ];

    foreach ($rows as $key => $row) {
      // If current player won return 0
      if (
        $squares[$row[0]] === $squares[$row[1]]
        && $squares[$row[0]] === $squares[$row[2]]
        && $squares[$row[0]] === 0
      )
        return 0;

      // If opponent won return 1
      if (
        $squares[$row[0]] === $squares[$row[1]]
        && $squares[$row[0]] === $squares[$row[2]]
        && $squares[$row[0]] === 1
      )
        return 1;

      // If there is a draw return -1
      if ($key === (sizeof($rows) - 1) && sizeof(array_filter($squares, function ($square) {
        return $square === null || $square === "SKIP";
      })) === 0) return -1;
    }

    // If the result is neither a win, a lose nor a draw, return false
    return false;
  }

  public $moves = [];
  public $paths = 0;

  // Children used for immediate calculation
  public $movesStatus = [
    'draw' => 0,
    'loser' => 0,
    'winner' => 0
  ];

  // Paths used for stadistics
  public $statistics = [
    'drawMoves' => 0,
    'loserMoves' => 0,
    'winnerMoves' => 0,

  ];

  public function __construct(
    public $squares,
    public $opponentTurn,
    public $searchOnly = null,
    public $name = "root",
    public $depth = 0
  ) {


    $this->squares = array_map(function ($el) {
      return ($el === "SKIP") ? null : $el;
    }, $squares);
    if ($this->searchOnly === null) $this->searchOnly = array_filter($this->squares, function ($val) {
      return $val === null;
    });

    // Build the tree
    $this->buildTree();

    if ($this->opponentTurn) {
      // It's opponent turn

      // Check for paths that force me to win
      if ($this->movesStatus['winner'] === sizeof($this->moves)) {
        $this->status = "winner";
      }

      // Check for paths that force me to lose
      if ($this->movesStatus['loser'] > 0) {
        $this->status = "loser";
      }
    } else {
      // It's my turn

      // Check for paths that force me to win
      if ($this->movesStatus['winner'] > 0) {
        $this->status = "winner";
      }

      // Check for paths that force me to lose
      if ($this->movesStatus['loser'] === sizeof($this->moves)) {
        $this->status = "loser";
      }
    }

    // Unset unnecessary properties
    unset($this->squares);
    unset($this->searchOnly);
  }

  private function buildTree()
  {
    // Build the children's tree
    foreach ($this->searchOnly as $square => $value) {
      // Prevent from infinite loop
      checkTime();

      // Make a copy of the squares to prevent modifying it until the result of the operations is ready
      $squaresCopy = $this->squares;
      $squaresCopy[$square] = intval($this->opponentTurn);
      $result = $this->check($squaresCopy, $this->name);

      // If there is no result
      if ($result === false) {
        $node = new tttTree(
          $squaresCopy,
          !$this->opponentTurn,
          null,
          $square,
          $this->depth + 1
        );
      } else {
        // This square musn't be used in the next iterations of this foreach, instead it will be possible
        // to use it within the children, where the SKIP will be replaced by a null
        $this->squares[$square] = "SKIP";

        // As there is a result, the node won't be a tree. It will contain the necessary information only
        $node = new tttNode($result, $square);
      }

      // Update paths
      if (isset($node->status))
        $this->statistics[$node->status . "Moves"]++;
      if (isset($node->statistics)) {
        foreach ($node->statistics as $moveType => $ammount)
          $this->statistics[$moveType] += $ammount;
      }

      if (isset($node->status)) {
        // Updates loserChilds, winnerChilds, discouragedChilds, etc...
        if (isset($this->movesStatus[$node->status])) $this->movesStatus[$node->status]++;
      }

      // Set the processed child
      $this->moves[$square] = $node;
    }
  }
}
