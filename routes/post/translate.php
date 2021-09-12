<?php
return [
  'translate/' => function () {
    if (isset($_POST['str'])) {
      $str = preg_replace("/[\r\n:]/", "", $_POST['str']);
      $translationsResource = resource('translations');
      $translationsResource->set($str, '')->sort()->save();

      return $translationsResource->all();
    }
  },
  'translate/keys' => function () {
  }
];
