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
    private $notes = [];

    /**
     * getNoteCount
     * 
     * @return string: the number of notes in the database
     */
    public function getNoteCount()
    {
        return count($this->notes);
    }
    
    /**
     * Get the note for this $guid
     * 
     * @param string $guid
     * @return Note|NULL
     */
    function getNote($guid)
    {
        foreach ($this->notes as &$storedNote) {
            if ($storedNote->getGuid() == $guid) {
                return $storedNote;
            }
        }
        
        return null;
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
        
        for ($i=0; $i<count($words); $i++) {
            $words[$i] = [
                'term' => $words[$i],
                'wildcardAt' => strpos($words[$i], '*')  
            ];
        }
        
        $noteCollection = new NoteCollection();
        
        foreach ($this->notes as $note) {
            $matchesAll = true;
            foreach ($words as $word) {
                $keyword = $word['term'];
                if ($keyword == '*') {
                    continue;
                }
                $wildcardAt = $word['wildcardAt'];

                if (preg_match('/^tag:(.*)/', $keyword, $matches)) {
                    if ($wildcardAt === false) {
                        if (!$note->hasTag($matches[1])) {
                            $matchesAll = false;
                            break;
                        }
                    } else {
                        if (!$note->hasPartTag($matches[1], $wildcardAt - 4)) {
                            $matchesAll = false;
                            break;
                        }
                    }
                } elseif (preg_match('/^created:(.*)/', $keyword, $matches)) {
                    if (!$note->createdOnOrAfter($matches[1])) {
                        $matchesAll = false;
                        break;
                    }
                } else {
                    if ($wildcardAt === false) {
                        if (!$note->hasKeyword($keyword)) {
                            $matchesAll = false;
                            break;
                        }
                    } else {
                        if (!$note->hasPartWord(substr($keyword, 0, strlen($keyword)-1))) {
                            $matchesAll = false;
                            break;
                        }
                    }
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
        unset($this->notes[$guid]);
    }
    
    /**
     * updateNote
     * Create, or, if it exists, update, the note with GUID=$guid
     * 
     * @param string $guid
     */
    function updateNote($note)
    {
        $this->notes[$note->getGuid()] = $note;
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
    private $tags;

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
    private $wordPieces;
    
    /**
     * @var array
     */
    private $wordIndex;
    
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
     * @return the $tags
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param multitype: $tags
     */
    public function setTags($tag)
    {
        if (!is_array($tag)) {
            $tag = [$tag];
        }

        $this->tags = $tag;
    }

    /**
     * @return the $content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * hasKeyword
     *
     * @param string $keyword
     * @param number $wildcardAt - The position of the wildcard char, or false
     * @return boolean true if note content contains $keyword
     */
    function hasKeyword($keyword)
    {
        return isset($this->wordIndex[$keyword]);
    }
    
    /**
     * hasPartWord
     *
     * @param string $partword
     * @param number $wildcardAt - The position of the wildcard char, or false
     * @return boolean true if note content contains $keyword
     */
    function hasPartWord($partWord)
    {
        return isset($this->wordPieces[$partWord]);
    }
    
    /**
     * hasTag
     *
     * @param string $term
     * @return boolean true if note has associated tag, otherwise false
     */
    function hasTag($term)
    {
        foreach ($this->tags as $tag) {
            if ($tag == $term) return true;
        }
        return false;
    }
    
    /**
     * hasPartTag
     *
     * @param string $term
     * @param number $wildcardAt - The position of the wildcard char, or false
     * @return boolean true if note has associated tag, otherwise false
     */
    function hasPartTag($term, $wildcardAt)
    {
        foreach ($this->tags as $tag) {
            if (substr_compare($tag, $term, 0, $wildcardAt) == 0) return true;
        }
        return false;
    }
    
    /**
     * createdOnOrAfter
     *
     * @param string $dateString
     * @return boolean true if note was created on or after $dateString
     */
    function createdOnOrAfter($dateString)
    {
        if ($this->created >= strtotime($dateString . 'T00:00:00+0000')) {
            return true;
        }
    
        return false;
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
        foreach ($words as $word) {
            $wordPart = '';
            foreach (str_split($word) as $char) {
                $this->wordPieces[$wordPart .= $char] = true;
            }
            $this->wordIndex[$word] = true;
        }
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

        if (count($this->tags) == 1) {
            $array['tag'] = $this->tags[0];
        }

        if (count($this->tags) > 1) {
            $array['tag'] = $this->tags;
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

        $this->setTags($tag);
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
