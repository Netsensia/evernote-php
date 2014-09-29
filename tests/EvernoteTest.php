<?php
include '../vendor/autoload.php';
include '../NoteStore.php';

define('RUNNING_UNIT_TESTS', true);

class NoteStoreTest extends PHPUnit_Framework_TestCase
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
        $noteStore = new NoteStore();
        
        $note = new Note();
        $note->exchangeArray($this->note[0]);
        $noteStore->updateNote($note);
        
        $this->assertEquals($noteStore->getNoteCount(), 1);
        
        $document = $noteStore->getNote('GUID1');
        $this->assertEquals($this->note[0], $document->getArrayCopy());
        $this->assertEquals($document->getGuid(), 'GUID1');
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
        $noteStore = new NoteStore();
    
        for ($i=0; $i<3; $i++) {
            $note = new Note();
            $note->exchangeArray($this->note[$i]);
            $noteStore->updateNote($note);
        }
    
        $this->assertEquals($noteStore->getNoteCount(), 3);
        $this->assertEquals($this->note[0], $noteStore->getNote('GUID1')->getArrayCopy());
        $this->assertEquals($this->note[1], $noteStore->getNote('GUID2')->getArrayCopy());
        $this->assertEquals($this->note[2], $noteStore->getNote('GUID3')->getArrayCopy());
    
        $noteStore->deleteNote('GUID2');
        $this->assertNull($noteStore->getNote('GUID2'));
        $this->assertEquals($noteStore->getNoteCount(), 2);
    
        $noteStore->deleteNote('GUID1');
        $this->assertNull($noteStore->getNote('GUID1'));
        $this->assertEquals($noteStore->getNoteCount(), 1);
    
        $noteStore->deleteNote('GUID3');
        $this->assertNull($noteStore->getNote('GUID3'));
        $this->assertEquals($noteStore->getNoteCount(), 0);
    }
    
    public function testUpdateNote()
    {
        $noteStore = new NoteStore();
    
        for ($i=0; $i<3; $i++) {
            $note = new Note();
            $note->exchangeArray($this->note[$i]);
            $noteStore->updateNote($note);
        }
    
        $this->assertEquals($noteStore->getNoteCount(), 3);
        $this->assertEquals($this->note[0], $noteStore->getNote('GUID1')->getArrayCopy());
        $this->assertEquals($this->note[1], $noteStore->getNote('GUID2')->getArrayCopy());
        $this->assertEquals($this->note[2], $noteStore->getNote('GUID3')->getArrayCopy());
    
        $note = $noteStore->getNote('GUID2');
        $newContent = 'New Content';
        $note->setContent($newContent);
        $noteStore->updateNote($note);
        $newNote = $noteStore->getNote('GUID2');
        
        $this->assertEquals($noteStore->getNoteCount(), 3);
        $this->assertEquals($newContent, $newNote->getContent());
    }
    
    public function testMatch()
    {
        $noteStore = new NoteStore();
        
        // Simple match
//         $this->assertTrue(Util::match('test', 'test', false));   
//         $this->assertFalse(Util::match('test2', 'test', false));
        
//         // Wildcard match
//         $this->assertTrue(Util::match('test2', 'test', 4));
//         $this->assertFalse(Util::match('test2', 'test3', 5));
//         $this->assertTrue(Util::match('test2', 't', 1));
//         $this->assertTrue(Util::match('test2', 'test', 4));
        
    }
    
    public function testInputs()
    {
        for ($i=1; $i<=4; $i++) {
            $noteStore = new NoteReader();
            $inputFile = 'input' . $i;
            $outputFile = 'output' . $i;
            $expectedOutput = 'expected' . $i;
            $noteStore->go($inputFile, $outputFile);
            $this->assertFileEquals($expectedOutput, $outputFile, 'Test case ' . $i);
        }
    }
    
    public function testLoad()
    {
        $noteStore = new NoteReader();
        $noteStore->go('inputload', '/dev/null');
    }
}