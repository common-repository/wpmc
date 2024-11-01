<?php
/*
Plugin Name: WPMC
Plugin URI: http://www.towerdefensehq.de/WPMC/
Description: This Plugin shows your MiniCity XML Data in your Word Press Blog and automatically creates links to the Mini City Site in regards of your demands.
Author: Karsten Eichentopf, comm-press GmbH & Co KG
Author URI: http://www.comm-press.net
Version: 1

		License

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    Please see <http://www.gnu.org/licenses/>.
*/

add_filter('the_content', 'WPMC_Content', 50);
add_filter('the_content', 'WPMC_Link', 50);
add_action('admin_menu', 'WPMC_add_pages');
register_activation_hook(__FILE__, 'WPMC_FirstRun');

function WPMC_add_pages() {
	add_options_page('WPMC', 'WPMC', 5, 'WPMCADMIN', 'WPMCADMIN');
}

function WPMC_FirstRun(){
	add_option('WPMCLASTRUN', 0, 'Unix timestamp of the last run', false);
	add_option('WPMCNAME', '', 'Name of your mini city', false);
	add_option('WPMCTHRIND', 3, 'Industry threshold', false);
	add_option('WPMCTHRTRA', 97, 'Transport threshold', false);
	add_option('WPMCTHRSEC', 3, 'Security threshold', false);
	add_option('WPMCTHRENV', 3, 'Enviroment threshold', false);
	add_option('WPMCTHRCOM', 10, 'Commercial every X hits', false);
	add_option('WPMCCOM', 0, 'Commercial hits', false);
	add_option('WPMCREFRESH', 5, 'Refresh XML every X minutes', false);
	add_option('WPMCDISPLAY', '<table border="1" cellspacing="1" cellpadding="2">
<tr>
<th colspan="2"><a href="%link%">%name%</a></th>
</tr>

<tr>
<td>Ranking</td><td>%ranking%</td>
</tr>

<tr>
<td>Population</td><td>%population%</td>
</tr>

<tr>
<td>Revenues</td><td>%incomes%</td>
</tr>

<tr>
<td>Region</td><td>%region code%</td>
</tr>

<tr>
<td>Unemployment</td><td>%unemployment%%</td>
</tr>

<tr>
<td>Transport</td><td>%transport%%</td>
</tr>

<tr>
<td>Criminality</td><td>%criminality%%</td>
</tr>

<tr>
<td>Pollution</td><td>%pollution%%</td>
</tr>
</table>', 'Default display style for content pages
.', false);
}



//use this inside of an <a href="{minicity_link:turmstadt}">Turmstadt</a>
function WPMC_Link($content){
	if(stristr($content,'{minicity_link}')){
		$Link = WPMC_getLink();
		$content = preg_replace('~\{minicity_link\}~',$Link,$content);
	}
	return $content;
}
function WPMC_Content($content){
	if(stristr($content,'{minicity}')){
    $mycity = WPMC_getCity();
    $content = preg_replace('~\{minicity\}~i',WPMC_displayCity(),$content);
	}
	return $content;
}

function WPMC_displayCity($mycity_content = ''){
  $mycity = WPMC_getCity();
  if(!$mycity_content){
	 $mycity_content = get_option('WPMCDISPLAY');
	}
	foreach($mycity AS $name => $value){
		if($name == 'error' && $value){
			$mycity_content = '<b style="color:red;">'.$value.'</b>';
			break;
		}
		$mycity_content = str_replace('%'.$name.'%',$value,$mycity_content);
	}
	$mycity_content = str_replace('%link%',WPMC_getLink(),$mycity_content);
	return $mycity_content;
}

function WPMC_getCity(){
	$DIFF = time() - get_option('WPMCLASTRUN');
	$REFRESH = get_option('WPMCREFRESH');
//grab Data every 5 minutes
	if($DIFF < $REFRESH * 600){
		$mycity = unserialize(get_option('WPMC_DATA'));
	}else{
		$City = get_option('WPMCNAME');
		$xml = file_get_contents('http://'.$City.'.myminicity.com/xml');
		$mycity = array();
		$mycity['error'] = '';
		if(!$xml){
			$mycity['error'] = 'City not found!';
			return $mycity;
		}
		//strip the xml and slice it
		$xml = str_replace("\n",'',$xml);
		$xml = str_replace("\t",'',$xml);
		preg_match(
		'~<\?xml version=\"1\.0\" encoding=\"UTF\-8\"\?'.
		'><city><host>'.$City.'\.myminicity\.com</host>'.
		'<name>(.+)</name>'.
		'<region code=\"(.+)\">(.+)</region>'.
		'<ranking>(.+)</ranking>'.
		'<population>(.+)</population>'.
		'<incomes>(.+)</incomes>'.
		'<unemployment>(.+)</unemployment>'.
		'<transport>(.+)</transport>'.
		'<criminality>(.+)</criminality>'.
		'<pollution>(.+)</pollution>'.
		'<nextnuke>(.+)</nextnuke>'.
		'<signatures>(.+)</signatures>'.
		'<bases com=\"(.+)\" env=\"(.+)\" ind=\"(.+)\" sec=\"(.+)\" tra=\"(.+)\"/>'.
		'</city>~i'
		,$xml, $data
		);
		//convert into a nice array
		if(!$data[1]){
			$mycity['error'] = 'Data Error!';
			return $mycity;
		}
		$mycity['name'] = $data[1];
		$mycity['region_iso'] = $data[2];
		$mycity['region code'] = $data[3];
		$mycity['ranking'] = $data[4];
		$mycity['population'] = $data[5];
		$mycity['incomes'] = $data[6];
		$mycity['unemployment'] = $data[7];
		$mycity['transport'] = $data[8];
		$mycity['criminality'] = $data[9];
		$mycity['pollution'] = $data[10];
		$mycity['nextnuke'] = $data[11];
		$mycity['signatures'] = $data[12];
		$mycity['com'] = $data[13];
		$mycity['env'] = $data[14];
		$mycity['ind'] = $data[15];
		$mycity['sec'] = $data[16];
		$mycity['tra'] = $data[17];

		update_option('WPMCLASTRUN', time());
		update_option('WPMC_DATA', serialize($mycity));
	}
 return $mycity;
}

function WPMC_getLink(){

	$City = get_option('WPMCNAME');
	$WPMCCOM = get_option('WPMCCOM');
	$WPMCTHRCOM = get_option('WPMCTHRCOM');
	$THR_IND = (int)get_option('WPMCTHRIND');
	$THR_TRA = (int)get_option('WPMCTHRTRA');
	$THR_SEC = (int)get_option('WPMCTHRSEC');
	$THR_ENV = (int)get_option('$THRENV');
	$THR_TRA = (int)get_option('WPMCTHRTRA');
		//default 0 makes no sense! Has to be 100!
	if(!$THR_TRA){
		$THR_TRA = 100;
	}
	$mycity = WPMC_getCity();
	$base = 'http://'.$City.'.myminicity.com';

	$WPMC_DEMAND = 0;
	$Return = $base;

	//find demand. Highest has priority.
	if($mycity['unemployment'] > $THR_IND){
		$Return = $base.'/ind';
		$WPMC_DEMAND = $mycity['unemployment'] - $THR_IND;
	}

	//transport ofcourse has to be 100 - sctual value
	if($mycity['transport'] < $THR_TRA){
		$Return = $base.'/tra';
		$WPMC_DEMAND = 100 - $mycity['transport'] - $THR_IND;

	}

	if($mycity['criminality'] > $THR_SEC){
		$Return = $base.'/sec';
		$WPMC_DEMAND = $mycity['criminality'] - $THR_SEC;
	}

	if($mycity['pollution'] > $THR_ENV){
		$Return = $base.'/env';
		$WPMC_DEMAND = $mycity['pollution'] - $THR_ENV;
	}

	//commercial is only done if there are no other needs.
	if(!$WPMC_DEMAND && $mycity['population'] > 1000){
		++$WPMCCOM;
		if($WPMCCOM == $WPMCTHRCOM){
			$Return = $base.'/com';
			$WPMCCOM = 0;
		}
		update_option('WPMCCOM', $WPMCCOM);
	}

	return $Return;
}

function WPMCADMIN(){

	if($_POST['WPMCSBM']){
		update_option('WPMCNAME',substr($_POST['WPMCNAME'],0,20));
		update_option('WPMCTHRIND',(int)$_POST['WPMCTHRIND']);
		update_option('WPMCTHRTRA',(int)$_POST['WPMCTHRTRA']);
		update_option('WPMCTHRSEC',(int)$_POST['WPMCTHRSEC']);
		update_option('WPMCTHRENV',(int)$_POST['WPMCTHRENV']);
		update_option('WPMCDISPLAY',stripslashes(trim($_POST['WPMCDISPLAY'])));
		update_option('WPMCTHRCOM',(int)$_POST['WPMCTHRCOM']);
		update_option('WPMCREFRESH',(int)$_POST['WPMCREFRESH']);
	}

	$NAME = get_option('WPMCNAME');
	$DISPLAY = get_option('WPMCDISPLAY');
	$THR_IND = (int)get_option('WPMCTHRIND');
	$THR_TRA = (int)get_option('WPMCTHRTRA');
	//default 0 makes no sense! Has to be 100!
	if(!$THR_TRA){
		$THR_TRA = 100;
	}
	$THR_SEC = (int)get_option('WPMCTHRSEC');
	$THR_ENV = (int)get_option('WPMCTHRENV');
	$THR_COM = (int)get_option('WPMCTHRCOM');
	$WPMCREFRESH = (int)get_option('WPMCREFRESH');

  function WPMC_Sel($name, $selected){
    echo '<select name="'.$name.'" id="'.$name.'">';
    for($i = 0; $i<=100;++$i){
      echo '<option value="'.$i.'"'.($i==$selected?' selected="selcted"':'').'>'.$i.'</option>';
    }
    echo '</select>';
  }

?>
<div class="wrap">
<form method="post" action="/wp-admin/options-general.php?page=WPMCADMIN">
<h2>My Mini City</h2>
<fieldset style="border:1px solid #aaaaaa;">
<legend style="font-size:12pt;font-weight:bold;">City Name</legend>
<input type="text" name="WPMCNAME" size="90" value="<?php echo $NAME; ?>" />
</fieldset>
<fieldset style="border:1px solid #aaaaaa;">
<legend style="font-size:12pt;font-weight:bold;">Thresholds</legend>
<label for="WPMCTHRIND">Industry: </label><?php WPMC_Sel('WPMCTHRIND',$THR_IND); ?>
 <label for="WPMCTHRTRA">Transport: </label><?php WPMC_Sel('WPMCTHRTRA',$THR_TRA); ?>
 <label for="WPMCTHRSEC">Security: </label><?php WPMC_Sel('WPMCTHRSEC',$THR_SEC); ?>
 <label for="WPMCTHRENV">Enviroment: </label><?php WPMC_Sel('WPMCTHRENV',$THR_ENV); ?>

</fieldset>
<fieldset style="border:1px solid #aaaaaa;">
<legend style="font-size:12pt;font-weight:bold;">Commerce</legend>
Commerce link every <?php WPMC_Sel('WPMCTHRCOM',$THR_COM); ?> Hits if there are not other needs and population over 1.000.
</fieldset>
<fieldset style="border:1px solid #aaaaaa;">
<legend style="font-size:12pt;font-weight:bold;">Refresh XML</legend>
Refresh XML every <?php WPMC_Sel('WPMCREFRESH',$WPMCREFRESH); ?> Minutes. 0 loads on every hit!
</fieldset>
<fieldset style="border:1px solid #aaaaaa;">
<legend style="font-size:12pt;font-weight:bold;">Display</legend>

<h4>Variables</h4>
<select size="5" style="border:1px solid #aaaaaa;width:655px;">
<optgroup label="Base data">
<option>%name%</option>
<option>%region_iso%</option>
<option>%region code%</option>
<option>%ranking%</option>
</optgroup>
<optgroup label="Current values">
<option>%population%</option>
<option>%incomes%</option>
<option>%unemployment%</option>
<option>%transport%</option>
<option>%criminaoptionty%</option>
<option>%pollution%</option>
</optgroup>
<optgroup label="Option click values">
<option>%com%</option>
<option>%env%</option>
<option>%ind%</option>
<option>%sec%</option>
<option>%tra%</option>
</optgroup>
<optgroup label="Miscalleneous">
<option>%link%</option>
<option>%flash%</option>
</optgroup>
<optgroup label="Unknown">
<option>%nextnuke%</option>
<option>%signatures%</option>
</optgroup>
</select>
</ul>
<h4>HTML</h4>
<textarea name="WPMCDISPLAY" style="width:650px;height:200px;"><?php echo $DISPLAY; ?></textarea>
</fieldset>
<input type="submit" value="Update" name="WPMCSBM" style="width:665px;" />
</form>

<h2>How to use?</h2>
<p>If you just want a simple link to your city you can use this anywhere inside of the content probably in the href="" of an anchor:</p>

<p><b>{minicity_link}</b></p>

<p>If you want the full html output use this inside of the content:</p>

<p><b>{minicity}</b></p>

<p>For php integration use:</p>

<p><b>WPMC_getLink()</b></p>

<p>This function will return the link to you city. Do something like this:</p>
<p>
if(function_exists('WPMC_getLink')){<br />
echo '&lt;a href="'.WPMC_getLink().'"&gt;MyCity&lt;/a&gt;';<br />
}
</p>

<p><b>WPMC_displayCity()</b></p>

<p>This function will output the whole html. Use it like this:</p>

<p>if(function_exists('WPMC_displayCity')){<br />
	echo WPMC_displayCity();<br />
}</p>

<p>You can pass a different custom html to this function like this:</p>

<p>if(function_exists('WPMC_displayCity')){<br />
	echo WPMC_displayCity('&lt;h1&gt;%name%&lt;/h1&gt;');<br />
}</p>
</div>
<?php

}
?>
