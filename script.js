
 var ti_replace_divs = new Array('macro_add','macro_del','macro_edit','ti_info','macro_list');
  /**
    * Edit onChange handler
    * @param el  input element which has been changed
    * @desc  if an encode hidden input already exists, its value
    *        is re-encoded from the text input's value
    *        If not, a new encoded hidden input is created with the encoded 
    *        value.  The encode input value is used to substitute the new edit values
    *        in the php edit() function 
   */
    function ti_replace_encode (el) {
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
 
  function ti_replace_show(which) {
    for(var i in ti_replace_divs) {
      ti_getEL(ti_replace_divs[i]).style.display='none';
    }
    ti_getEL(which).style.display='block';
    ti_getEL('ti_info_btn').style.display='inline';
 }
 
     
 function ti_getEL(n) {     
      return document.getElementById(n);      
  }