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
    
    public function testCreateNote()
    {
        $evernote = new Evernote();
        
        $note = new Note();
        $note->exchangeArray($this->note[0]);
        $evernote->createNote($note);
        
        $this->assertEquals($evernote->getDocumentCount(), 1);
        
        $document = $evernote->getNote('GUID1');
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
    
    public function testSortNoteCollection()
    {
        $noteCollection = new NoteCollection();
        
        for ($i=0; $i<3; $i++) {
            $note = new Note();
            $note->exchangeArray($this->note[$i]);
            $noteCollection->addNote($note);
        }
        
        $noteCollection->sortByDate();
        $sortedNotes = $noteCollection->getNotes();

        $this->assertEquals($this->note[2], $sortedNotes[0]->getArrayCopy());
        $this->assertEquals($this->note[0], $sortedNotes[1]->getArrayCopy());
        $this->assertEquals($this->note[1], $sortedNotes[2]->getArrayCopy());
    }
    
    public function testDeleteNote()
    {
        $evernote = new Evernote();
    
        for ($i=0; $i<3; $i++) {
            $note = new Note();
            $note->exchangeArray($this->note[$i]);
            $evernote->createNote($note);
        }
    
        $this->assertEquals($evernote->getDocumentCount(), 3);
        $this->assertEquals($this->note[0], $evernote->getNote('GUID1')->getArrayCopy());
        $this->assertEquals($this->note[1], $evernote->getNote('GUID2')->getArrayCopy());
        $this->assertEquals($this->note[2], $evernote->getNote('GUID3')->getArrayCopy());
    
        $evernote->deleteNote('GUID2');
        $this->assertNull($evernote->getNote('GUID2'));
        $this->assertEquals($evernote->getDocumentCount(), 2);
    
        $evernote->deleteNote('GUID1');
        $this->assertNull($evernote->getNote('GUID1'));
        $this->assertEquals($evernote->getDocumentCount(), 1);
    
        $evernote->deleteNote('GUID3');
        $this->assertNull($evernote->getNote('GUID3'));
        $this->assertEquals($evernote->getDocumentCount(), 0);
    }
    
    public function testInput1()
    {
        $evernote = new Evernote();
        $evernote->go('input1', 'output1');
        $this->assertFileEquals('expected1', 'output1');
    }
    
    public function testInput2()
    {
        $evernote = new Evernote();
        $evernote->go('input2', 'output2');
        $this->assertFileEquals('expected2', 'output2');
    }
    
    public function testInput3()
    {
        $evernote = new Evernote();
        $evernote->go('input3', 'output3');
        $this->assertFileEquals('expected3', 'output3');
    }
}