<?php
include 'NoteStore.php';

date_default_timezone_set('UTC');

$evernote = new NoteStore();
$evernote->go('tests/input1');


