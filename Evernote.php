<?php
date_default_timezone_set('UTC');

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
    
    public function sortByDate()
    {
        usort($this->note, 'NoteCollection::sortByTime');    
    }
    
    static function sortByTime($a, $b)
    {
        $result = $a->getCreated() < $b->getCreated() ? -1 : 1;
        return $result;
    }
    
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
        $absoluteTime = str_replace('Z', '+0000', $created);
        $this->created = strtotime($absoluteTime);
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
    
    public function exchangeArray($array)
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

class Evernote 
{
    private $noteDatabase = [];
    
    function getDocumentCount()
    {
        return count($this->noteDatabase);
    }
    
    function getNote($guid)
    {
        foreach ($this->noteDatabase as &$storedNote) {
            if ($storedNote->getGuid() == $guid) {
                return $storedNote;
            }
        }
        
        return null;
    }
    
    function getXmlString($fp)
    {
        $xmlString = '';
        
        while ($line = fgets($fp)) {
            $xmlString .= $line;
            if (preg_match('/^<\/note>/', $line)) {
                break;
            }
        }
        
        $xmlString = preg_replace('/&/', '&amp;', $xmlString);
        
        return trim($xmlString);
    }
    
    function makeNoteFromXml($xmlString)
    {
        $xmlObj = simplexml_load_string($xmlString);
        
        $json = json_encode($xmlObj);
        $noteArray = json_decode($json, true);
        $note = new Note();
        $note->exchangeArray($noteArray);
        
        return $note;    
    }
    
    function readNote($fp)
    {
        $xmlString = $this->getXmlString($fp);
        return $this->makeNoteFromXml($xmlString);
    }
    
    function updateNote($note)
    {
        foreach ($this->noteDatabase as &$storedNote) {
            if ($storedNote->getGuid() == $note->getGuid()) {
                $storedNote = $note;
                return;
            }
        }
        $this->noteDatabase[] = $note;
    }
    
    function removeArrayElement($array, $i)
    {
        unset($array[$i]);
        return array_values($array);
    }
    
    function deleteNote($guid)
    {
        for ($i=0; $i<count($this->noteDatabase); $i++) {
            if ($this->noteDatabase[$i]->getGuid() == $guid) {
                $this->noteDatabase =
                    $this->removeArrayElement(
                        $this->noteDatabase,
                        $i
                    );
                return;
            }
        }
    }
    
    function readNextLine($fp)
    {
        $line = chop(fgets($fp));
        return $line;    
    }
    
    function match($string, $term)
    {
        $string = strtolower(trim($string));
        $term = strtolower(trim($term));
        
        $wildcardAt = strpos($term, '*');
        
        $isMatch = false;
        
        if ($wildcardAt === false) {
            $isMatch = ($string == $term);
        } else {
            $compareStringPart = substr($string, 0, $wildcardAt);
            $compareTermPart = substr($term, 0, $wildcardAt);
            $isMatch = $compareStringPart == $compareTermPart;
        }
        
        return $isMatch;
    }
    
    function hasTag($note, $term)
    {
        $tags = $note->getTag();
        
        foreach ($tags as $tag) {
            if ($this->match($tag, $term)) {
                return true;
            }
        }
        return false;
    }
    
    function createdOnOrAfter($note, $term)
    {
        $onOrAfter = strtotime($term . 'T00:00:00+0000');
    
        if ($note->getCreated() >= $onOrAfter) {
            return true;
        }
    
        return false;
    }
    
    function hasKeyword($note, $keyword)
    {
        $content = $note->getContent();
        
        $words = preg_split('/[^A-Za-z0-9\']/', $content);
        
        foreach ($words as $word) {
            if (trim($word) != '') {
                if ($this->match($word, $keyword)) {
                    return true;
                }
            }
        }
        return false;
    }
    
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
    
    function go($input = "php://stdin", $output = "php://stdout")
    {
        $fp = fopen($input, "r");
        $fpo = fopen($output, "w");

        while (!feof($fp)) {
            
            fscanf($fp, "%s\n", $command);
            switch ($command) {
                case 'CREATE':
                case 'UPDATE':
                    $note = $this->readNote($fp);
                    $this->updateNote($note);
                    break;
                case 'DELETE':
                    $guid = $this->readNextLine($fp);
                    $this->deleteNote($guid);
                    break;   
                case 'SEARCH':
                    $term = $this->readNextLine($fp);
                    $noteCollection = $this->search($term);
                    $notes = $noteCollection->getNotes();
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
