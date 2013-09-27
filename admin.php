<?php
/**
 * 
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Myron Turner <turnermm02@shaw.ca>
 */
if(!defined('DOKU_INC')) die();

define('REPLACE_DIR', DOKU_INC . 'data/meta/macros/');
define('MACROS_FILE', REPLACE_DIR . 'macros.ser');
/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_textinsert extends DokuWiki_Admin_Plugin {

    var $output = false;
    var $macros_file;
    var $macros_data; //used for html listings
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
     $a = $this->get_macros();
     $macros = $_REQUEST['macro'];  
     $words = $_REQUEST['word'];      
    
      foreach ($macros AS $key=>$value) {
        if(isset($value) && trim($value)) {
           if(isset($words[$key]) && trim($words[$key])) { 
             $a[$value] = htmlspecialchars (($words[$key]),ENT_NOQUOTES, 'UTF-8');            
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
          $macros[$k] = htmlspecialchars ($val,ENT_NOQUOTES, 'UTF-8', false);            
       }
        io_saveFile(MACROS_FILE,serialize($macros));
        return $macros;
    }

    function get_macros() {
       if(file_exists(MACROS_FILE)) {          
           $a = unserialize(file_get_contents(MACROS_FILE));
          if(!is_array($a)) return array();
           ksort($a); 
           return $a;
       }
       return array();
    }

   function get_delete_list() {
      $macros = $this->macros_data;
      ptln('<table cellspacing="4px" width="90%">');
      foreach($macros as $macro=>$subst) {
          ptln("<tr><td><input type='checkbox' name='delete[$macro]' value='$subst'>"); 
          ptln( "<td style='padding:4px;'>$macro<td>$subst</td>");
           
      }
      ptln('</table>');
   }

   function get_edit_list() {
      $macros = $this->macros_data;
      ptln('<table cellspacing="4"><tr><th align="center">Macro</th><th align="center">' . $this->getLang('col_subst') .'</th></tr>');
      foreach($macros as $macro=>$subst) {
          ptln("<tr><td align='center'>$macro&nbsp;</td><td>");
          $encoded = urlencode($subst);        
          if($subst != $encoded) { 
             ptln("<input type = 'hidden' name='encoded[$macro]' value='$encoded'>");
          }
          if(strlen($subst) > 80) {
            ptln ("<textarea cols='55' rows='3' name='edit[$macro]' onchange='replace_encode(this)'>$subst</textarea></td></tr>");            

          }
          else {
            ptln ("<input type='text' size='80' name='edit[$macro]' onchange='replace_encode(this)' value='$subst'></td></tr>");            
          }

      }
      ptln('</table>');
   }

   function view_entries() {
      $macros = $this->macros_data;
      ptln('<table cellpadding="8px"  width="90%">');
      foreach($macros as $macro=>$subst) {
          ptln( "<tr><td align='center'>$macro<td style='padding: 4px; border-bottom: 1px solid black;'>$subst</tr>");
         
      }
      ptln('</table>');
   }

   function js() {
  
echo <<<JSFN

 <script type="text/javascript">
 //<![CDATA[ 
    var replace_divs= new Array('macro_add','macro_del','macro_edit','ti_info','macro_list');
   /**
    * Edit onChange handler
    * @param el  input element which has been changed
    * @desc  if an encode hidden input already exists, its value
    *        is re-encoded from the text input's value
    *        If not, a new encoded hidden input is created with the encoded 
    *        value.  The encode input value is used to substitute the new edit values
    *        in the php edit() function 
   */
    function replace_encode (el) {
      var matches = el.name.match(/\[(.*)\]/);
      if(matches[1]) {  
        var name = 'encoded['+matches[1]+']';
        var val = el.value;
        val = val.replace(/>/g,"&gt;"); 
        val = val.replace(/</g,"&lt;");          
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
    
 function ti_getEL(n) {     
      return document.getElementById(n);      
  }
  
 function replace_show(which) {
    for(var i in replace_divs) {
      ti_getEL(replace_divs[i]).style.display='none';
    }
    ti_getEL(which).style.display='block';
    ti_getEL('ti_info_btn').style.display='inline';
 }
//]]> 
 </script>
JSFN;

   }
    /**
     * output appropriate html
     */
    function html() {
     $this->macros_data = $this->get_macros();
     $this->js();
      if($this->output) {
        ptln('<pre>' . $this->output . '</pre>');
      }
      ptln('<div style="padding:4px" id="ti_info">');     
      ptln('<div style="text-align:right;">');
      ptln('<button class="button" style="padding:0px;margin:0px;" onclick="replace_show(\'ti_info_btn\');">');
      ptln($this->getLang('hide_info') .'</button>&nbsp;&nbsp;&nbsp;&nbsp;');
      ptln('</div>');
      ptln('<h2>Info</h2>');
      ptln( $this->locale_xhtml(intro) . '</div>');   
     
      ptln('<div style="padding-bottom:8px;">');
      ptln('<button class="button" onclick="replace_show(\'macro_add\'); ">');
      ptln($this->getLang('add_macros') .'</button>&nbsp;&nbsp;');
     
      ptln('<button class="button" onclick="replace_show(\'macro_del\'); ">');
      ptln($this->getLang('delete_macros') .'</button>&nbsp;&nbsp;');

      ptln('<button class="button" onclick="replace_show(\'macro_edit\'); ">');
      ptln($this->getLang('edit_macros') .'</button>&nbsp;&nbsp;');

      ptln('<button class="button" onclick="ti_getEL(\'macro_list\').style.display=\'block\';ti_getEL(\'macro_list\').scrollIntoView();">');
      ptln($this->getLang('view_macros') .'</button>&nbsp;&nbsp;');

      ptln('<button class="button" onclick="ti_getEL(\'macro_list\').style.display=\'none\';">');
      ptln($this->getLang('hide_macros') .'</button>&nbsp;&nbsp;');

      ptln('<button class="button" id="ti_info_btn" style="display:none" onclick="ti_getEL(\'ti_info\').style.display=\'block\';">');
      ptln($this->getLang('show_info') .'</button>');
   
      ptln('</div>');
      ptln('<form action="'.wl($ID).'" method="post">');
      
      // output hidden values to ensure dokuwiki will return back to this plugin
      ptln('  <input type="hidden" name="do"   value="admin" />');
      ptln('  <input type="hidden" name="page" value="'.$this->getPluginName().'" />');
      formSecurityToken();

      ptln('<div id="macro_add" style="display:none">');
      ptln('<h2>' . $this->getLang('label_add') . '</h2>');
      ptln( '<table cellspacing="8px"><tr><th>Macro</th><th>' . $this->getLang('col_subst') . '</th></tr>');
      ptln('<tr><td>  <input type="text" name="macro[A]" id="m_A" value="" /></td>');
      ptln('<td>  <input type="text" name="word[A]"  size="80" id="w_A" value="" /></td></tr>');
      ptln('<tr><td>  <input type="text" name="macro[B]" id="m_B" value="" /></td>');
      ptln('<td>  <input type="text" name="word[B]" size="80" id="w_B" value="" /></td></tr>');
      ptln('<tr><td>  <input type="text" name="macro[C]" id="m_C" value="" /></td>');
      ptln('<td>  <input type="text" name="word[C]" size="80"  id="w_C" value="" /></td></tr>');
      ptln('<tr><td>  <input type="text" name="macro[D]" id="m_D" value="" /></td>');
      ptln('<td>  <input type="text" name="word[D]" size="80" id="w_C" value="" /></td></tr>');
      ptln('<tr><td>  <input type="text" name="macro[E]" id="m_E" value="" /></td>');
      ptln('<td>  <input type="text" name="word[E]" size="80" id="w_E" value="" /></td>');
      ptln('<tr><td>  <input type="text" name="macro[F]" id="m_F" value="" /></td>');
      ptln('<td>  <textarea cols="45" name="word[F]" rows="4" id="w_F"></textarea></td>');
      ptln('</table>');      
      ptln('  <input type="submit" name="cmd[add]"  value="'.$this->getLang('btn_add').'" />');
      ptln('</div><br />');

      ptln('<div id="macro_del" style="display:none">');
      ptln('<h2>' . $this->getLang('label_del') . '</h2>');
      $this->get_delete_list();
      ptln('<br /><input type="submit" name="cmd[delete]"  value="'.$this->getLang('btn_del').'" />');
      ptln('</div>');    

      ptln('<div id="macro_edit" style="display:none; padding: 8px;">');
      ptln('<h2>' . $this->getLang('label_edit') . '</h2>');
      $this->get_edit_list();
      ptln('<br /><input type="submit" name="cmd[edit]"  value="'.$this->getLang('btn_edit').'" />');
      ptln('</div>');    

      ptln('</form>');

      ptln('<br /><div id="macro_list" style="overflow:auto;display:block;">');
      ptln('<h2>' . $this->getLang('label_list') .  '</h2>');
      $this->view_entries(); 
      ptln('</div>');
    }
 
}
