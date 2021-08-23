<?php
/**
 * 
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Myron Turner <turnermm02@shaw.ca>
 * 
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
define('REPLACE_DIR', DOKU_INC . 'data/meta/macros/');
define('MACROS_FILE', REPLACE_DIR . 'macros.ser');


/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_textinsert extends DokuWiki_Syntax_Plugin {
   var $macros;
   var $translations;
   var $ns;
    /**
     * return some info
     */

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /**
     * Where to sort in?
     */ 
    function getSort(){
        return 155;
    }


    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('#@\!?[\w\-\._]+\!?@#',$mode,'plugin_textinsert');
        $this->Lexer->addSpecialPattern('#@\!\![\w\-\._]+@#',$mode,'plugin_textinsert');
		$this->Lexer->addSpecialPattern('#@[\w\-\._]+~.*?~@#',$mode,'plugin_textinsert');
        $this->Lexer->addSpecialPattern('#@[\w\-\._]+[\r\n]+~[^\r\n]+~@#',$mode,'plugin_textinsert');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
		
        $html=false;
		$translation = false;
        $match = substr($match,2,-2); 
        $match = trim($match);   
        if(strpos($match, 'HTML')) $html=true;
        if(strpos($match, 'LANG_') !== false) {
		    $translation=true;
			list($prefix,$trans) = explode('_',$match,2);
			}
		
			global $ID;
			list($ns,$rest) = explode(':',$ID,2);			 
				if(@file_exists($filename = DOKU_PLUGIN . "textinsert/lang/$ns/lang.php")) {
					include $filename;
					$this->translations = $lang;
               
            }
 			
			if(@file_exists($filename = DOKU_PLUGIN . "textinsert/lang/$ns/macros.php")) {
					include $filename;   
                    $ar = 'lang_' .$ns;           
                    $tr = $$ar;	            				
                    if($this->translations)  {
                          $this->translations = array_merge($lang,$tr);                       
                    }
				    else $this->translations = $tr;
           }
    
           if(!empty($ns)) {
               $this->ns = $ns;
           }
        $this->macros = $this->get_macros();
		
       
       
        while(preg_match('#(\*\*|//|__|\'\').*?\1#m',$match )) { 
            $match = preg_replace_callback(
            '#(\*\*|//|__|\'\')(.*?)(\1)#',
                function($matches) {
                    $matches[1] = str_replace(array('**','//','__','\'\'',),array('<b>','<em>','<u>','<code>'),$matches[1]);
                   $matches[3] = str_replace(array('**','//','__','\'\''),array('</b>','</em>','</u>','</code>'),$matches[3]);    
                    return $matches[1] . $matches[2] . $matches[3];
                },$match );
        }		
        
		if(preg_match('/(.*?)~([\s\S]+)~$/',$match,$subtitution)) {
             $match=$subtitution[1];
             $subtitution[2] = str_replace('\\,','&#44;',$subtitution[2]);
             $substitutions=explode(',',$subtitution[2]);	
             $substitutions = preg_replace('#\/\/.+#',"",$substitutions);        
             $substitutions = preg_replace('#\\\n#',"<br />",$substitutions);    
		}
   
        if(!array_key_exists($match, $this->macros) ) {
            $err = $this->getLang('not_found');
           msg("$match $err", -1);  
           $match = "";              
        }
        else {
			if($translation && isset($this->translations[$trans])){
				$match = $this->translations[$trans];
			}
			else {
				$match =$this->macros[$match];                
			}
		   }
		  
		if(!is_array($substitutions)) $substitutions = array();
		for($i=0; $i<count($substitutions); $i++) {
	            $search = '%' . ($i+1);
	            $match = str_replace ($search ,  trim($substitutions[$i]), $match);
        }	
        
        $match = $this->get_inserts($match,$translation); 

        if($html) {
          $match =  str_replace('&lt;','<',$match);
          $match =  str_replace('&gt;','>',$match);
        }

        return array($state,$match);
    }

    /**
     * Create output
     */
    function render($mode, Doku_Renderer $renderer, $data) {
        global $INFO;
        if($mode == 'xhtml'){           
            list($state, $word) = $data;          
            If(strpos($word,'_ID_') !== false ) {               
                $word = str_replace('_ID_',$INFO['id'], $word);      
            }
           else If(strpos($word,'_NS_') !== false ) {               
                $word = str_replace('_NS_',getNS($INFO['id']), $word);      
            }
            else If(strpos($word,'_DATE_') !== false ) {               
                $word = str_replace('_DATE_',date("j-n-Y") , $word);      
            }           
            $renderer->doc .= $word;
            return true;
        }
        return false;
    }
    
    function get_macros() {
        $a = array();
        if(file_exists(MACROS_FILE)) {
            $a = unserialize(file_get_contents(MACROS_FILE));
       }
       else if($this->getConf('farm')) { 
           $a = unserialize(file_get_contents(metaFN('macros','.ser')));
       }
       $r =  $this->get_std_replacements() ;
       $result = array_merge($r,$a);   
       return array_merge($r,$a);        
    }

   function get_inserts($match,$translation) {
      $inserts = array();    
	  
	  // replace embedded macros
      if(preg_match_all('/#@(.*?)@#/',$match,$inserts)) {        
		$keys = $inserts[1]; 
		$pats = $inserts[0];        

		for($i=0; $i<count($keys); $i++) {
		   $insert = $this->macros[$keys[$i]];
			if($translation ||strpos($keys[$i], 'LANG_') !== false)  {
					list($prefix,$trans) = explode('_',$keys[$i],2);
					$_insert = $this->translations[$trans];
					if($_insert) $insert =$_insert;
			}
			$match = str_replace($pats[$i],$insert,$match);
          }
		  
      }  // end replace embedded macros
    
     
      $entities =  getEntities();
      $e_keys = array_keys($entities);
      $e_values =  array_values($entities);
      $match = str_replace($e_keys,$e_values,$match);    
      
      return  $match;
   }
  
  function get_std_replacements() {
        if(!$this->getConf('stdreplace')) return array();
        global $conf;
        global $INFO;
        global $ID;

        $file = noNS($ID);       
        $page = cleanID($file) ;
	
        $names =array(
                              'ID',
                              'NS',
                              'FILE',
                              '!FILE',
                              '!FILE!',
                              'PAGE',
                              '!PAGE',
                              '!!PAGE',
                              '!PAGE!',
                              'USER',
                              'DATE',
                              '_ID_',
                              '_NS_',
                              '_DATE_'
                              );
                              
            $values = array(
                              $ID,
                              getNS($ID),
                              $file,
                              utf8_ucfirst($file),
                              utf8_strtoupper($file),
                              $page,
                              utf8_ucfirst($page),
                              utf8_ucwords($page),
                              utf8_strtoupper($page),
                              $_SERVER['REMOTE_USER'],                              
                              strftime($conf['dformat'], time()),
                              '_ID_',
                              '_NS_',
                              '_DATE_'                              
                           );
     $std_replacements = array();
     for($i=0; $i<count($names) ; $i++) {
           $std_replacements[$names[$i]] = $values[$i];
     }     
   
     return $std_replacements;
}
  
  function write_debug($what, $screen = false) {
	  return;
	  $what=print_r($what,true);
       if($screen) {
       msg('<pre>' . $what . '</pre>');     
           return;
       }
	   $handle=fopen("textinsert.txt",'a');
	   fwrite($handle,"$what\n");
	   fclose($handle);
  }
}


