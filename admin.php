<?php
/**
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Myron Turner <turnermm02@shaw.ca>
 */
if(!defined('DOKU_INC')) die();

define('textinsert_DIR', DOKU_INC . 'data/meta/macros/');
define('MACROS_FILE', textinsert_DIR . 'macros.ser');
/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_textinsert extends DokuWiki_Admin_Plugin {

    var $output = false;
    var $macros_file;
    /**
     * handle user request
     */
    function handle() {
      $this->macros_file=MACROS_FILE;
    
      if (!isset($_REQUEST['cmd'])) return;   // first time - nothing to do

      $this->output = '';
      if (!checkSecurityToken()) return;
      if (!is_array($_REQUEST['cmd'])) return;
      $action = "";

      // verify valid values
      switch (key($_REQUEST['cmd'])) {
        case 'add' :
                $action = 'add';
                $a = $this->add();
              break;

        case 'delete' : 
                $a = $this->del();
                break;
        case 'edit':
             $a = $this->edit();
             break;
      }    
   //    $this->output = print_r($a,true);
   //  $this->output .= print_r($_REQUEST,true);
    }
 
    function add() {
     
     $a = unserialize(file_get_contents(MACROS_FILE)); 
     $macros = $_REQUEST['macro'];  
     $words = $_REQUEST['word'];      
    
      foreach ($macros AS $key=>$value) {
        if(isset($value) && trim($value)) {
           if(isset($words[$key]) && trim($words[$key])) { 
             $a[$value] = hsc($words[$key]);
         }
        }
     }
    
     io_saveFile(MACROS_FILE,serialize($a));
     return $a;
    }

    function del() {   

      $macros = $this->get_macros();

      $deletions = $_REQUEST['delete'];  
      $keys = array_keys($deletions);
      foreach ($keys AS $_key) {
         unset($macros[$_key]);        
      }
     
      io_saveFile(MACROS_FILE,serialize($macros));
      return $macros; 
    }

    function edit() {
       $macros = $this->get_macros();
            
       $encoded = $_REQUEST['encoded'];  
       $encoded = array_map(urldecode,$encoded);
       foreach($encoded AS $k=>$val) {
          $macros[$k] = $val;  
       }
        io_saveFile(MACROS_FILE,serialize($macros));
        return $macros;
    }

    function get_macros() {
       if(file_exists(MACROS_FILE)) {          
           $a = unserialize(file_get_contents(MACROS_FILE));
           ksort($a); 
           return $a;
       }
       return array();
    }

   function get_delete_list() {
      $macros = $this->get_macros();
      foreach($macros as $macro=>$subst) {
          ptln ("<input type='checkbox' name='delete[$macro]' value='$subst'>"); 
          ptln( "&nbsp;$macro = $subst<br />");
           
      }
   }

   function get_edit_list() {
      $macros = $this->get_macros();
      ptln('<table colspacing="4"><tr><th>Macro</th><th>Substitution</th></tr>');
      foreach($macros as $macro=>$subst) {
          ptln("<tr><td>$macro&nbsp;</td><td>");
          $encoded = urlencode($subst);
          $subst = hsc($subst); 
          if($subst != $encoded) { 
             ptln("<input type = 'hidden' name='encoded[$macro]' value='$encoded'>");
          }
          ptln ("<input type='text' size='80' name='edit[$macro]' onchange='textinsert_encode(this)' value='$subst'></td></tr>");            
      }
      ptln('</table>');
   }

   function view_entries() {
      $macros = $this->get_macros();
      foreach($macros as $macro=>$subst) {
          ptln( "$macro = $subst<br />"  );
         
      }
   }

   function js() {
  
echo <<<JSFN

 <script type="text/javascript">
 //<![CDATA[ 
    var textinsert_divs= new Array('macro_add','macro_del','macro_edit');
    function textinsert_encode (el) {
      var matches = el.name.match(/\[(.*)\]/);
      if(matches[1]) {  
        var name = 'encoded['+matches[1]+']';
        var val = el.value;
        val = val.textinsert(/>/g,"&gt;"); 
        val = val.textinsert(/</g,"&lt;");          
        if(!el.form[name]) {
            var encoder = document.createElement('input');           
            encoder.type = 'hidden';
            encoder.name = name;
            encoder.value = encodeURIComponent(val);
            el.form.appendChild(encoder);  
        }
        else if(el.form[name]) { 
          el.form[name].value = encodeURIComponent(val);
        }
      }
    }
 function textinsert_show(which) {
    for(var i in textinsert_divs) {
      $(textinsert_divs[i]).style.display='none';
    }
    $(which).style.display='block';
 }
//]]> 
 </script>
JSFN;

   }
    /**
     * output appropriate html
     */
    function html() {
     $this->js();
      if($this->output) {
        ptln('<pre>' . $this->output . '</pre>');
      }
      ptln('<div style="padding:4px">' . $this->getLang('msg') . '</div>');
     
      ptln('<div style="padding-bottom:8px;">');
      ptln('<button class="button" onclick="textinsert_show(\'macro_add\'); ">');
      ptln($this->getLang('add_macros') .'</button>&nbsp;');
     
      ptln('<button class="button" onclick="textinsert_show(\'macro_del\'); ">');
      ptln($this->getLang('delete_macros') .'</button>&nbsp;');

      ptln('<button class="button" onclick="textinsert_show(\'macro_edit\'); ">');
      ptln($this->getLang('edit_macros') .'</button>');

      ptln('<button class="button" onclick="$(\'macro_list\').style.display=\'block\';">');
      ptln($this->getLang('view_macros') .'</button>&nbsp;');

      ptln('<button class="button" onclick="$(\'macro_list\').style.display=\'none\';">');
      ptln($this->getLang('hide_macros') .'</button>');
   
      ptln('</div>');
      ptln('<form action="'.wl($ID).'" method="post">');
      
      // output hidden values to ensure dokuwiki will return back to this plugin
      ptln('  <input type="hidden" name="do"   value="admin" />');
      ptln('  <input type="hidden" name="page" value="'.$this->getPluginName().'" />');
      formSecurityToken();

      ptln('<div id="macro_add" style="display:none">');
      ptln( '<table><tr><th>Macro</th><th>' . $this->getLang('col_subst') . '</th></tr>');
      ptln('<tr><td>  <input type="text" name="macro[A]" id="m_A" value="" /></td>');
      ptln('<td>  <input type="text" name="word[A]"  id="w_A" value="" /></td></tr>');
      ptln('<tr><td>  <input type="text" name="macro[B]" id="m_B" value="" /></td>');
      ptln('<td>  <input type="text" name="word[B]" id="w_B" value="" /></td></tr>');
      ptln('<tr><td>  <input type="text" name="macro[C]" id="m_C" value="" /></td>');
      ptln('<td>  <input type="text" name="word[C]" id="w_C" value="" /></td></tr>');
      ptln('<tr><td>  <input type="text" name="macro[D]" id="m_D" value="" /></td>');
      ptln('<td>  <input type="text" name="word[D]" id="w_C" value="" /></td></tr>');
      ptln('<tr><td>  <input type="text" name="macro[E]" id="m_E" value="" /></td>');
      ptln('<td>  <input type="text" name="word[E]" id="w_E" value="" /></td>');
      ptln('</table>');      
      ptln('  <input type="submit" name="cmd[add]"  value="'.$this->getLang('btn_add').'" />');
      ptln('</div><br />');

      ptln('<div id="macro_del" style="display:none">');
      $this->get_delete_list();
      ptln('<br /><input type="submit" name="cmd[delete]"  value="'.$this->getLang('btn_del').'" />');
      ptln('</div>');    
      ptln('<div id="macro_edit" style="display:none; padding: 8px;">');
      $this->get_edit_list();
      ptln('<br /><input type="submit" name="cmd[edit]"  value="'.$this->getLang('btn_edit').'" />');
      ptln('</div>');    

      ptln('</form>');

      ptln('<br /><div id="macro_list" style="overflow:auto;display:block;">');
      ptln('<h2>Macro List</h2>');
      $this->view_entries(); 
      ptln('</div>');
    }
 
}
