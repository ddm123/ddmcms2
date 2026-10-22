/**
 * [$ID: common.js] Copyright (C) 2008-2011 DDM
 * http://www.jinshui8.com/
 */

var userAgent = navigator.userAgent.toLowerCase();
var is_opera = userAgent.indexOf('opera') != -1 && opera.version();
var is_moz = (navigator.product == 'Gecko') && userAgent.substr(userAgent.indexOf('firefox') + 8, 3);
var is_ie = (userAgent.indexOf('msie') != -1 && !is_opera) && userAgent.substr(userAgent.indexOf('msie') + 5, 3);
function $id(idname){
    return document.getElementById(idname);
}
function $name(name,form){
	var nodes = typeof form == 'undefined' ? document.getElementsByName(name) : form[name];
	var list = [];
	for(var i = 0,l = nodes.length;i<l;i++)list.push(nodes[i]);
    return list;
}
function $tag(tag,context,all){
	var o = typeof context == 'undefined' ? document.body : context;
	var nodes = o.getElementsByTagName(tag),list = [];
	if(typeof all == 'undefined')all = true;
	for(var i = 0,l = nodes.length;i<l;i++){
		if(all || nodes[i].parentNode==o)list.push(nodes[i]);
	}
    return list;
}
function Ajax(){
    var aj = new Object();
    aj.targetUrl = '';
    aj.sendString = '';
    aj.resultHandle = null;
    aj.contentType = 'html';
    aj.loading = '<img src="image/ajax_load.gif" style="margin: 3px; vertical-align: middle" /> Loading... ';
    aj.createXMLHttpRequest = function(){
        var request = false;
        if(window.XMLHttpRequest){
            request = new XMLHttpRequest();
        }else if(window.ActiveXObject){
            var versions = ['Microsoft.XMLHTTP', 'MSXML.XMLHTTP', 'Microsoft.XMLHTTP', 'Msxml2.XMLHTTP.7.0', 'Msxml2.XMLHTTP.6.0', 'Msxml2.XMLHTTP.5.0', 'Msxml2.XMLHTTP.4.0', 'MSXML2.XMLHTTP.3.0', 'MSXML2.XMLHTTP'];
            for(var i = 0; i < versions.length; i++){
                try{
                    request = new ActiveXObject(versions[i]);
                    if(request) return request;
                }catch($exception){/*alert($exception.message);*/}
            }
        }
        return request;
    };
    aj.request = aj.createXMLHttpRequest();
    aj.processHandle = function(){
        if(aj.request.readyState == 4){
            if(aj.request.status == 200){
                if(aj.contentType == 'xml'){
					try{aj.resultHandle(aj.request, aj.request.responseXML.documentElement.childNodes);}
					catch(e){aj.resultHandle(aj.request,false);}
                }else if(aj.contentType == 'json'){
                    try{aj.resultHandle(aj.request, eval("(" + aj.request.responseText + ")"));}
					catch(e){aj.resultHandle(aj.request,false);}
                }else{
                    aj.resultHandle(aj.request, aj.request.responseText);
                }
            }else if(aj.request.status){
                aj.resultHandle(aj.request, false);
            }
        }
    };
    aj.get = function(targetUrl, sendData, resultHandle, contentType){
        var sendString = "";
        if(typeof(sendData) == "object"){
            for(var key in sendData){
                sendString += "&" + key + "=" + encodeURIComponent(sendData[key]);
            }
        }else if(sendData && sendData != ""){
            sendString += "&" + sendData;
        }
        sendString = sendString.replace(/^&+/, "");
        aj.targetUrl = sendString == "" ? targetUrl : (targetUrl.indexOf("?") > 0 ? targetUrl + "&" + sendString : targetUrl + "?" + sendString);
        if(typeof(resultHandle) == "function"){
            aj.request.onreadystatechange = aj.processHandle;
            aj.resultHandle = resultHandle;
        }
        if(contentType)aj.contentType = contentType;
        if(window.XMLHttpRequest){
            aj.request.open('GET', aj.targetUrl);
            aj.request.send(null);
        }else{
            aj.request.open("GET", aj.targetUrl, true);
            aj.request.send();
        }
    };
    aj.post = function(targetUrl, sendData, resultHandle, contentType){
        var sendString = "";
        if(typeof(sendData) == "object"){
            for(var key in sendData){
                sendString += "&" + key + "=" + encodeURIComponent(sendData[key]);
            }
        }else if(sendData && sendData != ""){
            sendString += "&" + sendData;
        }
        sendString = sendString.replace(/^&+/, "");
        aj.targetUrl = targetUrl;
        aj.sendString = sendString;
        if(typeof(resultHandle) == "function"){
            aj.request.onreadystatechange = aj.processHandle;
            aj.resultHandle = resultHandle;
        }
        if(contentType)aj.contentType = contentType;
        aj.request.open('POST', targetUrl, true);
        aj.request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        aj.request.send(aj.sendString);
    };
    return aj;
}
function observer(obj, e, fun){
    if(document.attachEvent){
        obj.attachEvent("on" + e, fun);
    }else{
        obj.addEventListener(e, fun, false);
    }
}
function unobserver(obj, e, fun){
    if(document.detachEvent){
        obj.detachEvent("on" + e, fun);
    }else{
        obj.removeEventListener(e, fun, false);
    }
}
function DOMLoaded(fun){
	if(document.addEventListener){
		document.addEventListener('DOMContentLoaded',fun,false);
	}else{
		if(!window.DOMLoadedEventCallback){
			window.DOMLoadedEventCallback = new Array();
			var id = window.setInterval(function(){
				try{
					document.body.doScroll('left');
					window.clearInterval(id);
					var i;
					if(i = window.DOMLoadedEventCallback.length){
						for(;i--;)window.DOMLoadedEventCallback[i]();
					}else fun();
					window.DOMLoadedEventCallback = null;
				}catch($ex){}
			},1);
		}
		window.DOMLoadedEventCallback.push(fun);
	}
}
function add_tablerow(){
    var numargs = arguments.length;
    if(numargs < 2)return false;
    var i, tr, td;
    var o_tr = {"table":arguments[0], "tr":0, "td":[]};
    if(is_ie){
        tr = arguments[0].insertRow();
    }else{
        tr = document.createElement('tr');
        arguments[0].appendChild(tr);
    }
    o_tr.tr = tr;
    for(i = 1; i < numargs; i++){
        if(is_ie){
            td = tr.insertCell();
            td.innerHTML = arguments[i];
        }else{
            td = document.createElement('td');
            td.innerHTML = arguments[i];
            tr.appendChild(td);
        }
        o_tr.td.push(td);
    }
    return o_tr;
}
function getRadio(radioName){
    if(!radioName)return "";
    if(radioName.length){
        var len = radioName.length;
        for(var i = 0; i < len; i++){
            if(radioName[i].checked){
                return radioName[i].value;
            }
        }
    }else if(radioName.checked){
        return radioName.value;
    }
    return "";
}
function getSelect(selectName){
	var l = 0,r = '';
    if(selectName.options && (l = selectName.options.length)){
		if(selectName.multiple){
			for(var i = 0;i<l;i++)if(selectName.options[i].selected)r += (r==''?'':',')+selectName.options[i].value;
		}else r = selectName.options[selectName.selectedIndex].value;
    }
	return r;
}
function getCheckbox(checkboxName){
    if(!checkboxName)return "";
    var len = checkboxName.length;
    var values = "";
    if(len){
        for(var i = 0; i < len; i++){
            if(checkboxName[i].checked){
                values += "," + checkboxName[i].value;
            }
        }
    }else if(checkboxName.checked){
        values += "," + checkboxName.value;
    }
    if(values.length > 1){
        values = values.substr(1);
    }
    return values;
}
function getInputValue(input){
	if(!input)return "";
	if(input.type=="radio")return getRadio(input);
	if(input.type=="checkbox")return getCheckbox(input);
	return input.value;
}
function setSelect(selectobj, value){
    var len = selectobj.options.length;
    for(i = 0; i < len; i++){
        if(selectobj.options[i].value == value){
            selectobj.options[i].selected = true;
        }
    }
}
function checkall(name){
    var e = is_ie ? event : checkall.caller.arguments[0];
    obj = is_ie ? e.srcElement : e.target;
    var arr = document.getElementsByName(name);
    var k = arr.length;
    for(var i = 0; i < k; i++){
        arr[i].disabled || (arr[i].checked = obj.checked);
    }
}
function check_input(obj, defaultValue, minValue, maxValue){
    if(obj.value == "" || isNaN(obj.value)){
        obj.value = defaultValue;
    }else if(obj.value < minValue){
        obj.value = minValue;
    }else if(obj.value > maxValue){
        obj.value = maxValue;
    }
}
function is_mail(str){
    var re = /^[\w\-\.]+@([\w\-]+\.)+[\w\-]{2,4}$/i;
    return re.test(str);
}
function is_int(num,unsigned){
    var re = unsigned ? /^\d+$/ : /^[\-\+]?\d+$/;
    return re.test(num);
}
function is_float(num,unsigned){
    var re = unsigned ? /^\d+(\.\d+)?$/ : /^[\-\+]?\d+(\.\d+)?$/;
    return re.test(num);
}
function is_legalname(str){
    return !/[~!#\$%\^&\*\(\),=\+\\\|'\"\s]+/i.test(str);
}
function empty(str, zero){/* 如果zero为true,则字符串“0”不认为是空值 */
    if(zero && str === "0")return false;
    return !str || str == "" || str == "0" || str.length == 0 || /^\s+$/.test(str);
}
function trim(str){
    return str.replace(/(^\s*)|(\s*$)/g, '');
}
function cutstr(str, len){
    var newLength = 0;
    var newStr = "";
    var chineseRegex = /[^\x00-\xff]/g;
    var singleChar = "";
    var strLength = str.replace(chineseRegex, "**").length;
    len *= 2;
    if(strLength <= len)return str;else len -= 2;
    for(var i = 0; i < strLength; i++){
        singleChar = str.charAt(i).toString();
        newLength += singleChar.match(chineseRegex) == null ? 1 : 2;
        if(newLength > len)break;
        newStr += singleChar;
    }
    if(strLength > len)newStr += "...";
    return newStr;
}
function round(num, precision, returnstring){
    if(isNaN(num))return 0;
    if(!precision || !is_int(precision))precision = 0;
    var p = 1;
    for(var i = 0; i < precision; i++)p *= 10;
    var $num = p == 1 ? Math.round(num) : Math.round(num * p) / p;
    if(returnstring && precision>0){
        $num += 1 / p / 10;
        $num = $num.toString();
        $num = $num.substr(0,$num.indexOf(".") + precision + 1);
    }
    return $num;
}
function innerTEXT(elems){
    var ret = "", elem;
    for(var i = 0; elems[i]; i++){
        elem = elems[i];
        if(elem.nodeType === 3 || elem.nodeType === 4){
            ret += elem.nodeValue;
        }else if(elem.nodeType !== 8){
            ret += innerTEXT(elem.childNodes);
        }
    }
    return ret;
}
function merge(){
    var numargs = arguments.length;
    var i, key, return_value = false;
    if(numargs){
        return_value = {};
        for(i = 0; i < numargs; i++){
            if(typeof(arguments[i]) == "object"){
                for(key in arguments[i]){
                    return_value[key] = arguments[i][key];
                }
            }
        }
    }
    return return_value;
}
function in_array($needle, $haystack){
    if(typeof $haystack != "object")$haystack = [$haystack];
    for(var $key in $haystack){
        if($haystack[$key] == $needle){
            return true;
            break;
        }
    }
    return false;
}
function mousePosition(e, obj){
    var _x = e.clientX ? e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft) : e.pageX;
    var _y = e.clientY ? e.clientY + (document.documentElement.scrollTop || document.body.scrollTop) : e.pageY;
	_x += 10;_y += 15;
    if(obj.clientWidth + _x > document.documentElement.clientWidth + (document.documentElement.scrollLeft || document.body.scrollLeft) - 5){
        _x = _x - obj.clientWidth - 5;
    }
    if(obj.clientHeight + _y > document.documentElement.clientHeight + (document.documentElement.scrollTop || document.body.scrollTop) - 10){
        _y = _y - obj.clientHeight - 5;
    }
	obj.style.top = _y+'px';obj.style.left = _x+'px';
	return [_x,_y];
}
function moveXbySlicePos(x, obj){
    if(!document.layers){
        var par = obj;
        var lastOffset = 0;
        var onWindows = navigator.platform ? navigator.platform == "Win32" : false;
        try{
            var macIE45 = document.all && !onWindows && getExplorerVersion() == 4.5;
        }catch(exception){
            var macIE45 = false;
        }
        while(par){
            if(par.leftMargin && !onWindows) x += parseInt(par.leftMargin);
            if((par.offsetLeft != lastOffset) && par.offsetLeft) x += parseInt(par.offsetLeft);
            if(par.offsetLeft != 0) lastOffset = par.offsetLeft;
            par = macIE45 ? par.parentElement : par.offsetParent;
        }
    }else if(obj.x) x += obj.x;
    return x;
}
function moveYbySlicePos(y, obj){
    if(!document.layers){
        var par = obj;
        var lastOffset = 0;
        var onWindows = navigator.platform ? navigator.platform == "Win32" : false;
        try{
            var macIE45 = document.all && !onWindows && getExplorerVersion() == 4.5;
        }catch(exception){
            var macIE45 = false;
        }
        while(par){
            if(par.topMargin && !onWindows) y += parseInt(par.topMargin);
            if((par.offsetTop != lastOffset) && par.offsetTop) y += parseInt(par.offsetTop);
            if(par.offsetTop != 0) lastOffset = par.offsetTop;
            par = macIE45 ? par.parentElement : par.offsetParent;
        }
    }else if(obj.y >= 0) y += obj.y;
    return y;
}
function element_width(element, x){
    if(!element)return 0;
    if(!x)x = 0;
    if(element.tagName && element.tagName.toLowerCase() == "img"){
        if(element.width)return element.width + x;else{
            var image = new Image();
            image.src = element.src;
            return image.width + x;
        }
    }else{
        return Math.max(element.clientWidth, element.scrollWidth) + x;
    }
}
function element_height(element, y){
    if(!element)return 0;
    if(!y)y = 0;
    if(element.tagName && element.tagName.toLowerCase() == "img"){
        if(element.height)return element.height + y;else{
            var image = new Image();
            image.src = element.src;
            return image.height + y;
        }
    }else{
        return Math.max(element.clientHeight, element.scrollHeight) + y;
    }
}
function add_class(element, value){
    if(empty(element.className))element.className = value;
    else if((" " + element.className + " ").indexOf(" " + value + " ")<0)element.className += " " + value;
    return element;
}
function remove_class(element, value){
    var class_name = element.className;
    if(!empty(class_name)){
		class_name = (" " + class_name + " ").replace(" " + value + " ", " ").replace(/^(\s|\u00A0)+|(\s|\u00A0)+$/g, "");
		element.className = class_name;
	}
    return element;
}
function $notice(element, options){
	if(!element)return null;
    if(typeof(options) == "string" || typeof(options) == "number"){
        options = {'text':options};
    }else if(typeof(options) != "object"){
        options = null;
    }
	var idString = "notice-box-" + (element.getAttribute('id') ? element.getAttribute('id') : (element.getAttribute('name') ? element.getAttribute('name') : "common"));
	var noticeBox = document.getElementById(idString),noticeBoxBody;
	var parentBox = function(elm){
		var p = elm.parentNode,t;
		while(p){
			t = p.tagName;
			if(!t){p = false;break;}
			t = t.toLowerCase();
			if(t=='div' || t=='td' || t=='li' || t=='dd' || t=='form' || t=='body'){break;}
			if(t=='html'){p = false;break;}
			p = p.parentNode;
		}
		return p ? p : document.body;
	};
	if(options && options.text){
		if(!options.direction)options.direction = "up";
		if(!noticeBox){
			noticeBox = $e('div',parentBox(element),{"id":idString});
			noticeBoxBody = $e('div',noticeBox,{"id":idString+"-body"});
		}else{
			noticeBoxBody = document.getElementById(idString+"-body");
			noticeBox.style.top = null;
			//noticeBox.style.bottom = null;
			noticeBox.style.left = null;
			noticeBox.style.right = null;
		}
		noticeBox.className = "notice-box notice-box-"+options.direction;
		noticeBoxBody.className = options.icon ? "notice-body notice-body-icon notice-body-"+options.icon : "notice-body";
		noticeBoxBody.innerHTML = options.text;
		if(options.close && !document.getElementById(idString+"-closebtn")){
			$e('div',noticeBoxBody,{"id":idString+"-closebtn","className":"notice-closebtn","onclick":function(){noticeBox.style.display = "none";}});
		}
		noticeBox.style.display = "";
		var _x,_y;
		var noticeBoxWidth = element_width(noticeBox),noticeBoxHeight = element_height(noticeBox);
		var elementX = moveXbySlicePos(0,element),elementY = moveYbySlicePos(0,element);
		var elementWidth = element_width(element),elementHeight = element_height(element);
		var bodyScrollTop = document.documentElement.scrollTop || document.body.scrollTop;
		var bodyScrollLeft = document.documentElement.scrollLeft || document.body.scrollLeft;
		var bodyHeight = document.documentElement.clientHeight || document.body.clientHeight;
		var bodyWidth = document.documentElement.clientWidth || document.body.clientWidth;
		if(options.direction=='up' || options.direction=='down'){
			if(options.direction=='up'){
				_y = elementHeight+elementY+2;
				if(_y+noticeBoxHeight>=bodyScrollTop+bodyHeight){
					_y = elementY-noticeBoxHeight-2;
					noticeBox.className = noticeBox.className.replace("notice-box-up","notice-box-down");
				}
			}else{
				_y = elementY-noticeBoxHeight-2;
				if(_y<=bodyScrollTop){
					_y = elementHeight+elementY+2;
					noticeBox.className = noticeBox.className.replace("notice-box-down","notice-box-up");
				}
			}
			noticeBox.style.top = _y+"px";
			_x = elementX-6;
			if(noticeBoxWidth+_x>=bodyScrollLeft+bodyWidth){
				noticeBox.style.backgroundPosition = (noticeBoxWidth+_x-bodyWidth+20)+"px "+(noticeBox.className.lastIndexOf("notice-box-up")>=0 ? "0px" : "bottom");
				noticeBox.style.right = "2px";
			}else{
				noticeBox.style.left = _x+"px";
				noticeBox.style.backgroundPosition = "20px "+(noticeBox.className.lastIndexOf("notice-box-up")>=0 ? "0px" : "bottom");
			}
		}else{/* options.direction is 'left' or 'right' */
			if(options.direction=='left'){
				_x = elementX+elementWidth+6;
				if(_x+noticeBoxWidth>=bodyScrollLeft+bodyWidth){
					_x = elementX-noticeBoxWidth-6;
					if(_x<bodyScrollLeft+4){
						options.direction = 'down';
						return $notice(element, options);
					}
					noticeBox.className = noticeBox.className.replace("notice-box-left","notice-box-right");
				}
			}else{
				_x = elementX-noticeBoxWidth-6;
				if(_x<=bodyScrollLeft){
					_x = elementX+elementWidth+6;
					if(_x+noticeBoxWidth>=bodyScrollLeft+bodyWidth){
						options.direction = 'down';
						return $notice(element, options);
					}
					noticeBox.className = noticeBox.className.replace("notice-box-right","notice-box-left");
				}
			}
			noticeBox.style.left = _x+"px";
			_y = elementY-(elementHeight<38 ? 26-elementHeight/2 : 6);
			if(noticeBoxHeight+_y>bodyScrollTop+bodyHeight){
				var sc = noticeBoxHeight+_y-bodyHeight-bodyScrollTop;
				_y -= sc;
				noticeBox.style.backgroundPosition = (noticeBox.className.lastIndexOf("notice-box-left")>=0 ? "0px " : "right ")+(sc+20)+"px";
			}else{
				noticeBox.style.backgroundPosition = (noticeBox.className.lastIndexOf("notice-box-left")>=0 ? "0px " : "right ")+"20px";
			}
			noticeBox.style.top = _y+"px";
		}
	}else if(noticeBox){
		noticeBox.style.display = "none";
	}
	return noticeBox;
}
function $e(s_tag, app_node, params, style){
    var tag = document.createElement(s_tag);
    if(typeof app_node == "string")app_node = $id(app_node);
    if(typeof params == "object")for(var key in params)tag[key] = params[key];
    if(typeof style == "object")for(var key in style)tag.style[key] = style[key];
    if(app_node)app_node.appendChild(tag);else document.body.appendChild(tag);
    return tag;
}
function $e_(node){
    if(typeof node == "string")node = $id(node);
    if(node){
        try{
            node.removeNode(true);
        }catch($exception){
            node.parentNode.removeChild(node);
        }
    }
}
function disabled_body(v){
    var m = $id("body______mask");
    if(typeof v == "undefined")v = true;
    if(!m){
        if(!v)return m;
        m = $e("iframe", document.body, {"id":"body______mask", "src":"about:blank", "frameBorder":"0", "scrolling":"no", "bgcolor":"#FFFFFF"}, {"position":"absolute", "opacity":0.6, "filter":"alpha(opacity=60)", "display":"none", "top":"0px", "left":"0px", "height":"100%", "width":"100%", "background":"#FFFFFF", "zIndex":99});
    }
    if(v){
        m.style.width = element_width(document.documentElement || document.body) + "px";
        m.style.height = element_height(document.documentElement || document.body) + "px";
        m.style.display = "block";
    }else{
        m.style.display = "none";
    }
	return m;
}
function disabled(element, v, loading){
    var tag_name = element.tagName ? element.tagName.toLowerCase() : '';
    var mask_loading = null,mask = null,id_load_string = "body__loading",is_body = tag_name == "body" || tag_name == "html";
    if(typeof v == "undefined")v = true;
    if(is_body){
        mask = disabled_body(v);
    }else if(tag_name == "div" || tag_name == "form"){
        var id_string = "disabled_mask_" + (element.getAttribute('id') ? element.getAttribute('id') : (element.getAttribute('name') ? element.getAttribute('name') : 'common'));
        mask = $id(id_string);
        id_load_string = id_string + "_loading";
        if(!mask){
            if(!v){
                remove_class(element, 'disabled_relative');
                return {'mask':mask,'loading':$id(id_load_string)};
            }
            mask = $e("iframe", element, {"id":id_string, "src":"about:blank", "frameBorder":"0", "scrolling":"no", "bgcolor":"#FFFFFF"}, {"position":"absolute", "opacity":0.6, "filter":"alpha(opacity=60)", "display":"none", "top":"-1px", "left":"-1px", "height":"100%", "width":"100%", "background":"#FFFFFF", "zIndex":99});
        }
        if(v){
            mask.style.width = (Math.max(Math.max(element.clientWidth, element.offsetWidth), element.scrollWidth) + 2) + "px";
            mask.style.height = (Math.max(Math.max(element.clientHeight, element.offsetHeight), element.scrollHeight) + 2) + "px";
            mask.style.display = "block";
            add_class(element, 'disabled_relative');
        }else{
            mask.style.display = "none";
            remove_class(element, 'disabled_relative');
        }
    }
    mask_loading = $id(id_load_string);
    if(v && loading && loading != ""){
        if(!mask_loading){
            mask_loading = $e("div", element, {"id":id_load_string, "className":"disabled_loading"});
            mask_loading.style.position = is_body ? 'fixed' : 'absolute';
        }
        mask_loading.innerHTML = '<span>' + loading + '</span>';
        mask_loading.style.display = "";
        mask_loading.style.left = ((Math.max(element.clientWidth, element.offsetWidth) - mask_loading.clientWidth) / 2) + "px";
        mask_loading.style.top = ((Math.max(element.clientHeight, is_body ? 0 : element.offsetHeight) - mask_loading.clientHeight) / 2) + "px";
    }else if(mask_loading){
        mask_loading.style.display = "none";
    }
	return {'mask':mask,'loading':mask_loading};
}
function doane(event){
    var e = event ? event : window.event;
    if(is_ie){
        e.returnValue = false;
        e.cancelBubble = true;
    }else if(e){
        e.stopPropagation();
        e.preventDefault();
    }
}
function getFormElements(form,urlcode){
	var f = form.elements,l = f.length,e = {length:0,elements:[]},u = '';
	for(var i=0;i<l;i++){
		if(f[i].name && f[i].name!=''){
			if(urlcode){
				if(f[i].disabled || (f[i].type && (f[i].type=='radio' || f[i].type=='checkbox') && !f[i].checked))continue;
				if(u!='')u += '&';
				u += f[i].name+'='+encodeURIComponent(f[i].tagName.toLowerCase()=="select" ? getSelect(f[i]) : f[i].value);
			}else{
				if(f[i].name=='length' || f[i].name=='elements'){
					throw "There are not supported in the form element name named 'length' or 'elements'";
				}else if(typeof e[f[i].name] == 'undefined'){
					e.elements.push(e[f[i].name] = form[f[i].name]);
					e.length++;
				}
			}
		}
	}
	return urlcode ? u : e;
}
function validateForm(form,onsuccess,noticeDir){
	var self = this;
	var cmboboxs = false;
	this.form = form;
	this.noticeDir = noticeDir;
	this.onsuccess = typeof onsuccess == 'function' ? onsuccess : false;
	this.validateResult = false;
	var _getParent = function(elm){
		var p = elm.parentNode,pt = p.tagName.toLowerCase();
		if(pt=='label' || pt=='li')p = _getParent(p);
		return p;
	};
	this.validate = function(){
		if(!this.form)return null;
		var elms = getFormElements(this.form);
		var result = false,i,verify,v,em,verifyResult,re,e,isGroup = false;
		for(i=0; i<elms.length; i++){
			try{
				e = elms.elements[i];
				verify = e.getAttribute("verify");
				isGroup = false;
			}catch(ex){
				e = elms.elements[i][0];
				verify = e.getAttribute("verify");
				isGroup = true;
			}
			if(!empty(verify) && empty(e.readonly) && !e.disabled){
				if(e.tagName.toLowerCase()=="select")v = getSelect(elms.elements[i]);
				else if(e.type && e.type=='radio')v = getRadio(elms.elements[i]);
				else if(e.type && e.type=='checkbox')v = getCheckbox(elms.elements[i]);
				else v = e.value;
				if(empty(v,true))verifyResult = !empty(e.getAttribute("empty"));
				else if(verify=="int")verifyResult = is_int(v);
				else if(verify=="float")verifyResult = is_float(v);
				else if(verify=="email"||verify=="mail")verifyResult = is_mail(v);
				else if(re = verify.match(/^\/(.+)\/([A-Za-z]+)?$/)){
					var reg = re[2] ? new RegExp(re[1],re[2]) : new RegExp(re[1]);
					verifyResult = reg.test(v);
				}else{verifyResult = true;}
				if(verifyResult){var relm = isGroup ? _getParent(e) : e;remove_class(relm,"form-invalid");$notice(relm);}
				else{result = e;em = e.getAttribute("errormsg");break;}
			}
		}
		if(result){
			var relm = isGroup ? _getParent(result) : result;
			add_class(relm,"form-invalid");
			if(!em || em=='')em = relm.getAttribute("errormsg");
			if(em && em!="")$notice(relm,{"text":em,"direction":typeof this.noticeDir=="string" ? this.noticeDir : "left","icon":"error","close":true});
			if(!isGroup){try{result.focus();}catch(e){}}
			this.validateResult = false;
		}else if(!this.validateCmboboxs())this.validateResult = false;
		else this.validateResult = true;
		return this.validateResult;
	};
	this.validateCmboboxs = function(){
		for(var cmboboxs = this.getCmboboxs(),i = cmboboxs.length,inp;i--;){
			inp = $id('selectbox-hidden'+cmboboxs[i].getAttribute('hash'));
			if(inp && empty(inp.value,true)){
				add_class($id('selectbox-input'+cmboboxs[i].getAttribute('hash')),"form-invalid");
				if(cmboboxs[i].errormsg)$notice(cmboboxs[i],{"text":cmboboxs[i].errormsg,"direction":typeof this.noticeDir=="string" ? this.noticeDir : "left","icon":"error","close":true});
				return false;
			}else{
				remove_class($id('selectbox-input'+cmboboxs[i].getAttribute('hash')),"form-invalid");
				if(cmboboxs[i].errormsg)$notice(cmboboxs[i]);
			}
		}
		return true;
	};
	this.getCmboboxs = function(){
		if(cmboboxs===false){
			cmboboxs = [];
			var spans = this.form.getElementsByTagName('span');
			for(var i = spans.length;i--;){
				if(spans[i].className=='combobox-container' && spans[i].parentNode.getAttribute("verify")){
					spans[i].errormsg = spans[i].parentNode.getAttribute("errormsg");
					cmboboxs.push(spans[i]);
				}
			}
		}
		return cmboboxs;
	};
	if(this.form){
		observer(this.form,"submit",function(ev){
			var form = ev.target || window.event.srcElement;
			if(self.validate(form)){
				if(typeof self.onsuccess == 'function')self.onsuccess(form,ev,self);
			}else{
				doane(ev);
			}
		});
	}
}