<?php
/**
 * Info Plugin: Displays information about various DokuWiki internals
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Myron Turner <turnermm02@shaw.ca>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
define('textinsert_DIR', DOKU_INC . 'data/meta/macros/');
define('MACROS_FILE', textinsert_DIR . 'macros.ser');


/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_textinsert extends DokuWiki_Syntax_Plugin {

    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Myron Turner',
            'email'  => 'turnermm02@shaw.ca',
           'date'   => '2011-05-13',
            'name'   => 'word textinsertment Plugin',
            'desc'   => 'textinsert Macros with words',
            'url'    => 'http://www.dokuwik.org/plugin:wordtextinsert',
        );
    }

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
        $this->Lexer->addSpecialPattern('#@[\w\-\._]+@#',$mode,'plugin_textinsert');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        $match = substr($match,2,-2); 
        $match = trim($match);   
        $macros = $this->get_macros();
        if(!array_key_exists($match, $macros)) {
           msg("$match macro was not found in the macros database", -1);  
           $match = "";              
        }
        else $match =$macros[$match];
        
        return array($state,$match);
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml'){
            list($state, $word) = $data;
            $renderer->doc .= $word;
            return true;
        }
        return false;
    }
    
    function get_macros() {
       if(file_exists(MACROS_FILE)) {
          return unserialize(file_get_contents(MACROS_FILE));
       }
       return array();
    }

   
}


