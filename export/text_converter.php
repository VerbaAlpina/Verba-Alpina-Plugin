<?php
abstract class VA_TextConverter {
    public abstract function export();
    public abstract function get_extension();
    public abstract function get_mime();
    
    protected $id;
    protected $lang;
    protected $db;
    
    public function __construct ($id, $db){
		$id_s = explode('_', $id);
		
        $this->id = $id_s[0];
        $this->lang = $id_s[1];
        $this->db = $db;
    }
}

class VA_HTML_TextConverter extends VA_TextConverter {
    public function export(){
        global $va_xxx;
        $va_xxx->select('va_xxx');
		
		global $vadb;
		$vadb->select($this->db); //Needed for va_get_post_version_id to work properly (there must not be another version selected for va_xxx)
		global $va_current_db_name;
		$va_current_db_name = $this->db; //Needed for version_newer_than to work in properly in parseSyntax
        
        $type = substr($this->id, 0, 1);
        
        switch ($type){
            case 'C':
            case 'L':
            case 'B':
                $text = va_get_comment_text($this->id, substr($this->lang, 0, 2), false, $vadb, true);
				break;
				
			case 'M':
				$text = $vadb->get_var($vadb->prepare('SELECT Erlaeuterung_' . substr($this->lang, 0, 1) . ' FROM glossar WHERE Id_Eintrag = %d', substr($this->id, 1)));
				parseSyntax($text, true, false, 'N', false, true);
				break;
				
			case 'P':
				if ($this->lang == 'deu'){
					$blog_id = 1;
				}
				else {
					$va_lang = strtoupper(substr($this->lang, 0, 1));
					$blog_id = va_blog_id_from_lang($va_lang);
					switch_to_blog($blog_id);
				}
				
				$vid = va_get_post_version_id(substr($this->id, 1), $this->db);
				$text = get_the_content(null, false, $vid);

				if ($this->lang != 'deu'){
					restore_current_blog();
				}
				
				parseSyntax($text, true, false, 'N', false, true);
		
				break;
                
            default:
                throw new ErrorException('Unknown id type: ' . $this->id);
        }
        
        if (!$text){
            throw new ErrorException('No text for this id!');
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