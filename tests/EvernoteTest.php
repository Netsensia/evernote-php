<?php
include '../vendor/autoload.php';
include '../Evernote.php';

define('RUNNING_UNIT_TESTS', true);

class EvernoteTest extends PHPUnit_Framework_TestCase
{
    private $note = [
        [
            'guid' => 'GUID1',
            'created' => '2014-05-03T11:13:08Z',
            'tag' => [
                'poetry',
                'byron',
            ],
            'content' =>
                'The Son of Love and Lord of War I sing; '
        ],
        [
            'guid' => 'GUID2',
            'created' => '2014-06-03T11:13:08Z',
            'content' =>
                'And Britain\'s bravest Victor was the last.'
        ],
        [
            'guid' => 'GUID3',
            'created' => '2014-03-03T11:13:08Z',
            'tag' => 'poetry',
            'content' =>
                'The Bastard kept, like lions, his prey fast, '
        ],
    ];
    
    public function testCreateDocument()
    {
        $evernote = new Evernote();
        
        $note = new Note();
        $note->exchangeArray($this->note[0]);
        $evernote->createDocument($note);
        
        $this->assertEquals($evernote->getDocumentCount(), 1);
        
        $document = $evernote->getDocument('GUID1');
        $this->assertEquals($this->note[0], $document->getArrayCopy());
        $this->assertEquals($document->getGuid(), 'GUID1');
    }
    
    public function testRemoveArrayElement()
    {
        $array = [1,2,3,4,5,6];
        $evernote = new Evernote();
        $array = $evernote->removeArrayElement($array, 3);
        
        $this->assertEquals([1,2,3,5,6], $array);
    }
    
    public function testDeleteDocument()
    {
        $evernote = new Evernote();
        
        for ($i=0; $i<3; $i++) {
            $note = new Note();
            $note->exchangeArray($this->note[$i]);
            $evernote->createDocument($note);
        }
        
        $this->assertEquals($evernote->getDocumentCount(), 3);
        $this->assertEquals($this->note[0], $evernote->getDocument('GUID1')->getArrayCopy());
        $this->assertEquals($this->note[1], $evernote->getDocument('GUID2')->getArrayCopy());
        $this->assertEquals($this->note[2], $evernote->getDocument('GUID3')->getArrayCopy());
        
        $evernote->deleteDocument('GUID2');
        
        $this->assertNull($evernote->getDocument('GUID2'));
        $this->assertEquals($evernote->getDocumentCount(), 2);
        
    }
}