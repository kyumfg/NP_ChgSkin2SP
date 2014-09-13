<?php
/* NP_ChgSkin2SP v1.1
 * 
 * UserAgentによりスマートホンを判別し、適切なSkinへ振り分け
 * Nakazoe氏(nakazoe@comiu.com)作 NP_AdjustSkin2MobileLite 0.2を改造
 * 
 * v1.0 2013.09.21 初版 kyu
 * v1.1 2014.05.08 Feed(RSS/ATOM)パースエラー修正  kyu
 */

class NP_ChgSkin2SP extends NucleusPlugin{
	var $SkinName;
	
	function getName(){return 'ChgSkin2SP';}
	function getAuthor(){return 'kyu';}
	function getURL(){return 'mailto:kyumfg@gmail.com';}
	function getVersion(){return '1.1[2014.05.08]';}
	function getMinNucleusVersion(){return '341';}
	function getDescription(){return 'UserAgentによりスマートホンを判別し、適切なSkinへ振り分けます。スキンに<%ChgSkin2SP%>と記述するとPC表示/スマートホン表示を切り替えるためのリンクを出力します。';}
	
	function getEventList(){
	    return array(
	        'InitSkinParse',
	    );
	}
	
	function install() {
	    $this->createOption('spskinname','スマートホン表示で使用するスキン名','text','smartphone');
	    $this->createOption('viewsp','スキン変数<%ChgSkin2SP%>: スマートホン表示するためのリンク名','text','スマートホン表示');
	    $this->createOption('viewpc','スキン変数<%ChgSkin2SP%>: PC表示するためのリンク名','text','PC表示');
	}
	
	function uninstall() {
	    $this->deleteOption('spskinname');
	    $this->deleteOption('viewsp');
	    $this->deleteOption('viewpc');
	}
	
	function supportsFeature($feature) {
	    switch($feature) {
	        case 'SqlTablePrefix':
	            return 1;
	        default:
	            return 0;
	    }
	}
	
	
	//スキンパース前処理
	function event_InitSkinParse(&$data){
	    $viewmode = getVar('viewmode');
	    if (is_null($viewmode)){
	        $viewmode = cookieVar('viewmode');
	    }
	    if (is_null($viewmode) == false){
	        $viewmode = intval($viewmode);
	    }
	    if ($viewmode == 0 || $viewmode == 1){
	        setcookie('viewmode', $viewmode);
	    }
	
	    $DefaultPCSkinName_str = $data['skin']->name;
	    $DefaultSPSkinName_str = htmlspecialchars($this->getOption('spskinname'), ENT_QUOTES);
	
	    if ($viewmode == 1 || is_null($viewmode)){
	        if ($this->isSmartPhone() && !SKIN::exists($DefaultSPSkinName_str)){
	            $DefaultSkinName = $DefaultPCSkinName_str;
	        }elseif ($this->isSmartPhone() && SKIN::exists($DefaultSPSkinName_str)){
	            $DefaultSkinName = $DefaultSPSkinName_str;
	        }
	    }elseif ($viewmode == 0){
	        $DefaultSkinName = $DefaultPCSkinName_str;
	    }else{
	        return;
	    }
	
	    If (strpos($_SERVER["REQUEST_URI"],".php") == true){
	        If (strpos($_SERVER["REQUEST_URI"],"index.php") == false){
	            return;
	        }
	    }
	
	    $SkinName = $DefaultSkinName;
	    $skin =& SKIN::createFromName($SkinName);
	    $data['skin']->SKIN($skin->getID());
	
	    return;
	}
	
	//スキン変数
	function doSkinVar(){
	    $viewmode = getVar('viewmode');
	    if (is_null($viewmode)){
	        $viewmode = cookieVar('viewmode');
	    }
	    if (is_null($viewmode)){
	        if ($this->isSmartPhone()){
	            $viewmode = 1;
	        }else{
	            $viewmode = 0;
	        }
	    }else{
	        $viewmode = intval($viewmode);
	    }
	
	    $Url = "http://". $_SERVER["HTTP_HOST"]. $_SERVER["REQUEST_URI"];
	    If (strpos($Url,"?") == false){
	        $Url .= "?viewmode";
	    }
	    If (strpos($Url,"viwemode") == false){
	        $Url .= "&";
	    }
	    $Url = str_replace(strstr($Url,"viewmode"),"",$Url);
	
	    $viewsp = htmlspecialchars($this->getOption('viewsp'), ENT_QUOTES);
	    $viewpc = htmlspecialchars($this->getOption('viewpc'), ENT_QUOTES);
	
	    if ($this->isSmartPhone()){
	        echo "<div class='viewmode'>";
	        If ($viewmode == 0){
	            echo "<a href='". $Url. "viewmode=1'>". $viewsp. "</a>";
	        }elseif ($viewmode == 1){
	            echo "<a href='". $Url. "viewmode=0'>", $viewpc. "</a>";
	        }else{
	            return;
	        }
	        echo "</div>";
	    }
	}
	
	
	//UA判定
	function Platform(){
	    $UA = explode("/",$_SERVER['HTTP_USER_AGENT']);
	
	    // iPhone
	    if(preg_match("/iPhone/",$_SERVER['HTTP_USER_AGENT'])){
	        $phone = array("Platform" => "iPhone","PlatformFlg" => 10);
	
	    // iPod Touch
	    }elseif(preg_match("/iPod/",$_SERVER['HTTP_USER_AGENT'])){
	        $phone = array("Platform" => "iPhone","PlatformFlg" => 11);
	
	    //android
	    }elseif(preg_match("/Android/",$_SERVER['HTTP_USER_AGENT'])){
	        $phone = array("Platform" => "Android","PlatformFlg" => 12);
	
	    //others(PC)
	    }else {
	        $phone = array("Platform" => "pc","PlatformFlg" => 9);
	    }
	    return $phone;
	}
	
	
	function isiPhone(){
	    $PlatForm_ary = $this->Platform();
	    if(
	        $PlatForm_ary["PlatformFlg"] == 10 || 
	        $PlatForm_ary["PlatformFlg"] == 11
	        
	    ){
	        return true;
	    }else{
	        return false;
	    }
	}
	
	function isAndroid(){
	    $PlatForm_ary = $this->Platform();
	    if($PlatForm_ary["PlatformFlg"] == 12){
	        return true;
	    }else{
	        return false;
	    }
	}
	
	function isSmartPhone(){
	    if(
	        $this->isiPhone() ||
	        $this->isAndroid()
	    ){
	        return true;
	    }else{
	        return false;
	    }
	}
}
