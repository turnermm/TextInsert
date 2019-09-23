<?php
/**
 * 
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Myron Turner <turnermm02@shaw.ca>
 *
 *             Modification by Hans-Juergen Schuemmer <hans-juergen.schuemmer@web.de>
 *             Additional syntax "MULTI" for multi line plugin 
 *             with separation of the parameters by "|...|" and line breaks in the output by "|+...|"
 *             Example:
 *             #@CMS_GEFAHR_MULTI~
 *             |Gefahr       |
 *             |Überschrift  |
 *             |+zweizeilig  |
 *             |Hinweistext  |
 *             ~@#
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
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
		
        $html=false;
		$translation = false;

		/* Modification for "MULTI": new variable "$multi": */
        $multi=false;

        $match = substr($match,2,-2); 
        $match = trim($match);   
		
        if(strpos($match, 'HTML')) $html=true;

		/* Modification for "MULTI": new request: */
        if(strpos($match, 'MULTI')) $multi=true;

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
    
		/* Modification for "MULTI": Removing line breaks: */
		if($multi) {
			/* eleminate all kind of line feeds in input */
			$match = str_replace(array("\r\n", "\r", "\n"), '', $match); 
			/* delete the first and the last occurrence of "|" to fit the original syntax */
			$match = str_replace(array("~|"), '~', $match);
			$match = str_replace(array("|~"), '~', $match);
			/* set a targeted line feed for the output */
			$match = str_replace(array("||+"), '<br>', $match);
		}
		
        $this->macros = $this->get_macros();
		
		if(preg_match('/(.*?)~(.*)~$/',$match,$subtitution)) {
			$match=$subtitution[1];

			/* Modification for "MULTI": substitution by "||" instead of ",": */
			if($multi) {
				$substitutions=explode('||',$subtitution[2]);}			
			else
				{$substitutions=explode(',',$subtitution[2]);
			}
		}
		  
        if(!array_key_exists($match, $this->macros) ) {
           multiline("$match macro was not found in the macros database", -1);  
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
		
		for($i=0; $i<count($substitutions); $i++) {
	            $search = '%' . ($i+1);
	            $match = str_replace ($search ,  $substitutions[$i], $match);
        }	
        
        $match = $this->get_inserts($match,$translation); 

        if($html) {
          $match =  str_replace('&lt;','<',$match);
          $match =  str_replace('&gt;','>',$match);
        }
        if($multi) {
          $match =  str_replace('&lt;','<',$match);
          $match =  str_replace('&gt;','>',$match);
        }
		
        return array($state,$match);
    }

    /**
     * Create output
     */
    function render($mode, Doku_Renderer $renderer, $data) {
        if($mode == 'xhtml'){           
            list($state, $word) = $data;
         
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
                              'EVENT'    
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
							  $event->name ,
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
       multiline('<pre>' . $what . '</pre>');     
           return;
       }
	   $handle=fopen("textinsert.txt",'a');
	   fwrite($handle,"$what\n");
	   fclose($handle);
  }
}
