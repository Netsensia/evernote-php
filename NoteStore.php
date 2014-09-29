<?php
date_default_timezone_set('UTC');

/**
 * Class NoteStore
 * 
 * A collection of notes and search methods
 */
class NoteStore 
{
    /**
     * An array of type Note
     */
    private $noteDatabase = [];

    /**
     * getNoteCount
     * 
     * @return string: the number of notes in the database
     */
    public function getNoteCount()
    {
        return count($this->noteDatabase);
    }
    
    /**
     * Get the note for this $guid
     * 
     * @param string $guid
     * @return Note|NULL
     */
    function getNote($guid)
    {
        foreach ($this->noteDatabase as &$storedNote) {
            if ($storedNote->getGuid() == $guid) {
                return $storedNote;
            }
        }
        
        return null;
    }
    
    /**
     * hasTag
     * 
     * @param Note $note
     * @param string $term
     * @return boolean true if note has associated tag, otherwise false
     */
    function hasTag(Note $note, $term)
    {
        $tags = $note->getTag();
        $wildcardAt = strpos($term, '*');
        
        foreach ($tags as $tag) {
            if (Util::match($tag, $term, $wildcardAt)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * createdOnOrAfter
     *
     * @param Note $note
     * @param string $dateString
     * @return boolean true if note was created on or after $dateString
     */
    function createdOnOrAfter($note, $dateString)
    {
        $onOrAfter = strtotime($dateString . 'T00:00:00+0000');
    
        if ($note->getCreated() >= $onOrAfter) {
            return true;
        }
    
        return false;
    }
    
    /**
     * hasKeyword
     *
     * @param Note $note
     * @param string $keyword
     * @return boolean true if note content contains $keyword
     */
    function hasKeyword(Note $note, $keyword)
    {
        $words = $note->getWords();
        $wildcardAt = strpos($keyword, '*');
        
        foreach ($words as $word) {
            if (Util::match($word, $keyword, $wildcardAt)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * search
     *
     * Parse the search term and return the GUIDS of all matching notes
     * sorted by date
     * 
     * @param string $term
     * @return NoteCollection
     */
    function search($term)
    {   
        $term = strtolower($term);
        $words = explode(' ', $term);
        
        $noteCollection = new NoteCollection();
        
        foreach ($this->noteDatabase as $note) {
            $matchesAll = true;
            foreach ($words as $word) {
                if (preg_match('/^tag:(.*)/', $word, $matches)) {
                    $func = 'hasTag';
                    $word = $matches[1];
                } elseif (preg_match('/^created:(.*)/', $word, $matches)) {
                    $func = 'createdOnOrAfter';
                    $word = $matches[1];
                } else {
                    $func = 'hasKeyword';
                }
                    
                if (!$this->$func($note, $word)) {
                    $matchesAll = false;
                    break;
                }
            }
            if ($matchesAll) {
                $noteCollection->addNote($note);
            }
        }
        
        $noteCollection->sortByDate();
        return $noteCollection;
    }
    
    /**
     * deleteNote
     * Remove the note with GUID=$guid from the database
     * 
     * @param string $guid
     */
    function deleteNote($guid)
    {
        unset($this->noteDatabase[$guid]);
    }
    
    /**
     * updateNote
     * Create, or, if it exists, update, the note with GUID=$guid
     * 
     * @param string $guid
     */
    function updateNote($note)
    {
        $this->noteDatabase[$note->getGuid()] = $note;
    }
}

class NoteCollection
{
    /**
     * @var array
     */
    private $note = [];

    /**
     * @param Note $note
    */
    public function addNote(Note $note)
    {
        $this->note[] = $note;
    }

    /**
     * Sort this note collection in ascending datetime order
     */
    public function sortByDate()
    {
        if (count($this->note) > 1) {
            usort($this->note, 'NoteCollection::sortByTime');
        }
    }

    /**
     * Helper function for usort
     *
     * @param Note $a
     * @param Note $b
     * @return number
     */
    static function sortByTime(Note $a, Note $b)
    {
        $result = $a->getCreated() < $b->getCreated() ? -1 : 1;
        return $result;
    }

    /**
     * Return the array of notes
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->note;
    }
}

class Note
{
    /**
     * @var string
     */
    private $guid;

    /**
     * @var array
     */
    private $tag;

    /**
     * @var string
     */
    private $content;

    /**
     * Timestamp
     *
     * @var number
     */
    private $created;

    /**
     * @var array
     */
    private $words;

    /**
     * @return the $words
     */
    public function getWords()
    {
        return $this->words;
    }

    /**
     * @param multitype: $words
     */
    public function setWords($words)
    {
        $this->words = $words;
    }

    /**
     * @return the $guid
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * @param string $guid
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;
    }

    /**
     * @return the $tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param multitype: $tag
     */
    public function setTag($tag)
    {
        if (!is_array($tag)) {
            $tag = [$tag];
        }

        $this->tag = $tag;
    }

    /**
     * @return the $content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;

        /*
         * Split on any character that is not alphanumeric, apostrophe or space
         * This will remove any such characters from the content except
         * for those embedded within words
         */
        $words = preg_split('/[^A-Za-z0-9\']/', strtolower($content));
        $newWords = [];
        foreach ($words as $word) {
            $word = trim($word);
            if ($word != '') {
                $newWords[] = $word;
            }
        }

        $this->setWords($newWords);
    }

    /**
     * @return the $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param number $created
     */
    public function setCreated($created)
    {
        $this->created = strtotime($created);
    }

    public function getArrayCopy()
    {
        $array = [
            'guid' => $this->guid,
        ];

        if (count($this->tag) == 1) {
            $array['tag'] = $this->tag[0];
        }

        if (count($this->tag) > 1) {
            $array['tag'] = $this->tag;
        }

        $array = array_merge(
            $array,
            [
                'content' => $this->content,
                'created' => date('Y-m-d\TH:i:s\Z', $this->created),
            ]
        );

        return $array;
    }

    /**
     * Create this note from an array definition
     *
     * @param array $array
     */
    public function exchangeArray(array $array)
    {
        $this->setGuid($array['guid']);
        $this->setContent($array['content']);
        $this->setCreated($array['created']);

        if (isset($array['tag'])) {
            if (is_array($array['tag'])) {
                $tag = $array['tag'];

            } else {
                $tag = [$array['tag']];
            }
        } else {
            $tag = [];
        }

        $this->setTag($tag);
    }
}

/**
 * Helper functions
 */
class Util
{
    static public function getXmlString($fp, $tag)
    {
        $xmlString = '';

        while ($line = fgets($fp)) {
            $xmlString .= $line;
            if (preg_match('/^<\/' . $tag . '>/', $line)) {
                break;
            }
        }

        $xmlString = preg_replace('/&/', '&amp;', $xmlString);

        return $xmlString;
    }

    static public function makeNoteFromXml($xmlString)
    {
        $xmlObj = simplexml_load_string($xmlString);

        $json = json_encode($xmlObj);
        $noteArray = json_decode($json, true);
        $note = new Note();
        $note->exchangeArray($noteArray);

        return $note;
    }

    static public function readNextLine($fp)
    {
        $line = chop(fgets($fp));
        return $line;
    }

    static public function readXml($fp, $tag)
    {
        $xmlString = Util::getXmlString($fp, $tag);
        return Util::makeNoteFromXml($xmlString);
    }

    static public function match($string, $term, $wildcardAt)
    {
        if ($wildcardAt === false) {
            return $string == $term;
        } else {
            return substr_compare($string, $term, 0, $wildcardAt) == 0;
        }
    }
}

/**
 * 
 * The STDIN reader loop
 *
 */
class NoteReader
{
    static public function go($input = "php://stdin", $output = "php://stdout")
    {
        $noteStore = new NoteStore();
        
        $fp = fopen($input, "r");
        $fpo = fopen($output, "w");
    
        while (!feof($fp)) {
    
            fscanf($fp, "%s\n", $command);
            switch ($command) {
                case 'CREATE':
                case 'UPDATE':
                    $note = Util::readXml($fp, 'note');
                    if ($input == 'inputload') {
                        $note->setGuid(uniqid());
                    }
                    $noteStore->updateNote($note);
                    break;
                case 'DELETE':
                    $guid = Util::readNextLine($fp);
                    $noteStore->deleteNote($guid);
                    break;
                case 'SEARCH':
                    $term = Util::readNextLine($fp);
                    $notes = $noteStore->search($term)->getNotes();
                    if (count($notes) == 0) {
                        fprintf($fpo, PHP_EOL);
                    } else {
                        $results = '';
                        foreach ($notes as $note) {
                            $results .= $note->getGuid() . ',';
                        }
                        fprintf($fpo, substr($results, 0, strlen($results)-1) . PHP_EOL);
                    }
                    break;
            }
        }
    
        fclose($fpo);
        fclose($fp);
    }
}
