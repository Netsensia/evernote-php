<?php
include '../vendor/autoload.php';
include '../Evernote.php';

define('RUNNING_UNIT_TESTS', true);

class EvernoteTest extends PHPUnit_Framework_TestCase
{
    public function testCreateDocument()
    {
        $note = [
            'guid' => 1,
            'created' => '2014-05-03T11:13:08Z',
            'tag' => [
                'poetry',
                'byron',
            ],
            'content' =>  
                'The Son of Love and Lord of War I sing; ' .
                'Him who bade England bow to Normandy, ' .
                'And left the name of Conqueror more than King ' .
                'To his unconquerable dynasty. ' .
                'Not fanned alone by Victory\'s fleeting wing, ' .
                'He reared his bold and brilliant throne on high; ' .
                'The Bastard kept, like lions, his prey fast, ' .
                'And Britain\'s bravest Victor was the last.'
        ];
        
        $evernote = new Evernote();
        $evernote->createDocument($note);

        $this->assertEquals($evernote->getDocumentCount(), 1);
        $this->assertEquals($evernote->getDocument(0), $note);
    }
}