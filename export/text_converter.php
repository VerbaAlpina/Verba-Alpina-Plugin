<?php
abstract class VA_TextConverter {
    public abstract function export();
    public abstract function get_extension();
    public abstract function get_mime();
    
    protected $id;
    protected $lang;
    protected $db;
    
    public function __construct ($id, $lang, $db){
        $this->id = $id;
        $this->lang = $lang;
        $this->db = $db;
    }
}

class VA_HTML_TextConverter extends VA_TextConverter {
    public function export(){
        global $va_xxx;
        $va_xxx->select($this->db);
        
        $type = substr($this->id, 0, 1);
        
        switch ($type){
            case 'C':
            case 'L':
            case 'B':
                $text = va_get_comment_text($this->id, $this->lang, false, $va_xxx);
				break;
                
            default:
                throw new ErrorException('Unknown id type: ' . $this->id);
        }
        
        if (!$text){
            throw new ErrorException('No text for this id and language!');
        }
        
        return $text;
    }
    
    public function get_extension(){
        return 'html';
    }
    
    public function get_mime(){
        return 'text/html';
    }
}