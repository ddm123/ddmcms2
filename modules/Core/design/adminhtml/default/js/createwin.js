//
// [createwin.js] Copyright (C) 2008-2009 DDM
//

function drag_element(obj_click, obj_drag, ambits){
	obj_drag || (obj_drag = obj_click);
	//this.odrag.style.cursor = "move";

	var self = this,z_index_incr = 999,onWindows = navigator.platform ? navigator.platform == "Win32" : false;
	//鼠标与对象X,Y轴相差的距离
	var xx = 0,yy = 0;
	var _x = 0,_y = 0;
	var o_width,o_height;

	var attach_Event = function(obj,e,fun){
		if(document.attachEvent){
			obj.attachEvent("on"+e,fun);
		}else{
			obj.addEventListener(e,fun,false);
		}
	};

	var detach_event = function(obj,e,fun){
		if(document.detachEvent){
			obj.detachEvent("on"+e,fun);
		}else{
			obj.removeEventListener(e,fun,false);
		}
	};

	var get_x = function (x, obj) {
		if (!document.layers) {
			//var onWindows = navigator.platform ? navigator.platform == "Win32" : false;
			var macIE45 = document.all && !onWindows && getExplorerVersion() == 4.5;
			var par = obj;
			var lastOffset = 0;
			while(par){
				if( par.leftMargin && ! onWindows ) x += parseInt(par.leftMargin);
				if( (par.offsetLeft != lastOffset) && par.offsetLeft ) x += parseInt(par.offsetLeft);
				if( par.offsetLeft != 0 ) lastOffset = par.offsetLeft;
				par = macIE45 ? par.parentElement : par.offsetParent;
			}
		} else if (obj.x) x += obj.x;
		return x;
	};

	var get_y = function (y, obj) {
		if(!document.layers) {
			//var onWindows = navigator.platform ? navigator.platform == "Win32" : false;
			var macIE45 = document.all && !onWindows && getExplorerVersion() == 4.5;
			var par = obj;
			var lastOffset = 0;
			while(par){
				if( par.topMargin && !onWindows ) y += parseInt(par.topMargin);
				if( (par.offsetTop != lastOffset) && par.offsetTop ) y += parseInt(par.offsetTop);
				if( par.offsetTop != 0 ) lastOffset = par.offsetTop;
				par = macIE45 ? par.parentElement : par.offsetParent;
			}
		} else if (obj.y >= 0) y += obj.y;
		return y;
	};

	var mousemove = function(even){
		var mouse_x = even.x ? even.x+document.documentElement.scrollLeft : even.pageX;
		var mouse_y = even.y ? even.y+document.documentElement.scrollTop : even.pageY;
		//var odrag = even.srcElement || even.target;
		_x = mouse_x-xx;
		_y = mouse_y-yy;
		if(typeof(ambits)=='object'){
			if(ambits.x1!=undefined && _x < ambits.x1){
				_x = ambits.x1;
			}else if(ambits.x2!=undefined && _x > ambits.x2-o_width){
				_x = ambits.x2-o_width;
			}
			if(ambits.y1!=undefined && _y < ambits.y1){
				_y = ambits.y1;
			}else if(ambits.y2!=undefined && _y > ambits.y2-o_height){
				_y = ambits.y2-o_height;
			}
		}else if(ambits && ambits=='disabled')return;
		obj_drag.style.left = _x+"px";
		obj_drag.style.top = _y+"px";
		if(typeof(self.ondrag)=="function")self.ondrag(self,even,{"x":_x,"y":_y});
	};

	var start_drag = function(even){
		_x = get_x(0, obj_drag);
		_y = get_y(0, obj_drag);
		var mouse_x = even.x ? even.x+document.documentElement.scrollLeft : even.pageX;
		var mouse_y = even.y ? even.y+document.documentElement.scrollTop : even.pageY;
		xx = mouse_x-_x;
		yy = mouse_y-_y;
		obj_drag.style.zIndex = parseInt(obj_drag.style.zIndex)+z_index_incr;
		if(onWindows) {
			document.body.onselectstart = function() {
				return false;
			};
		}
		if(typeof(ambits)=='object'){
			o_width = element_width(obj_drag,0);o_height = element_height(obj_drag,0);
		}
		attach_Event(document.documentElement,"mousemove",mousemove);
		attach_Event(document.documentElement,"mouseup",stop_drag);
	};

	var stop_drag = function(even){
		obj_drag.style.zIndex = parseInt(obj_drag.style.zIndex)-z_index_incr;
		if(onWindows) {
			document.body.onselectstart = function() {
				return true;
			};
		}
		detach_event(document.documentElement,"mousemove",mousemove);
		detach_event(document.documentElement,"mouseup",stop_drag);
	};

	this.set_ambits = function(o){
		ambits = o;
		return this;
	};
	this.ondrag = null;

	attach_Event(obj_click,"mousedown",start_drag);
}

function createwin(width,height,left,top,title_text,body_text){
	var idkey = String(Math.random()).substr(2);
	var title,body,close_btn,maxsize_btn,title_label,self = this,owin = document.getElementById("owin"+idkey),divmask;
	var z_index = new Date().getTime()%1000+9000;

	width || (width = 300);
	height || (height = 300);
	left || (left = 0);
	top || (top = 0);

	var iframemask_style = {backgroundColor:"#FFFFFF",position:"absolute",zIndex:z_index,left:"0px",top:"0px",borderWidth:"0px",padding:"0px",margin:"0px",display:"none",filter:'alpha(opacity=60)',opacity:0.6};
	var owin_style = {backgroundColor:"#FFFFFF",textAlign:"left",position:"absolute",zIndex:z_index+1,left:left+"px",top:top+"px",border:"1px solid #666666",display:"none",fontSize:'12px',boxShadow:'2px 4px 10px 0px rgba(0, 0, 0, 0.65)'};
	var divmask_style = {borderWidth:"0px",padding:"0px",margin:"0px"};
	var title_style = {backgroundColor:"#2F2F2F",padding:"5px",cursor:"move",borderBottom:"1px solid #666666",color:"#FFFFFF",fontWeight:"bold"};
	var body_style = {padding:"0px",width:width+"px",height:(height-28)+"px",overflowY:"auto"};

	var set_style = function(element,style){
		for(var key in style){
			element.style[key] = style[key];
		}
	};
	var remove_elem = function(e){
		try{
            e.removeNode(true);
        }catch($exception){
            e.parentNode.removeChild(e);
        }
	};
	if(!owin){
		owin = document.createElement("div");
		divmask = document.createElement("div");
		title = document.createElement("div");
		body = document.createElement("div");
		title_label = document.createElement("span");

		set_style(owin,owin_style);
		set_style(divmask,divmask_style);
		set_style(title,title_style);
		set_style(body,body_style);
		owin.id = "owin"+idkey;
		divmask.id = "divmask"+idkey;
		title.id = "owin_title"+idkey;
		body.id = "owin_body"+idkey;
		title_label.id = "title_label"+idkey;

		document.body ? document.body.appendChild(owin) : document.documentElement.appendChild(owin);
		owin.appendChild(divmask);
		divmask.appendChild(title);
		divmask.appendChild(body);
		title.appendChild(title_label);
	}else{
		title_label = document.getElementById("title_label"+idkey);
		body = document.getElementById("owin_body"+idkey);
		title = document.getElementById("owin_title"+idkey);
	}
	//--------------------------------------------------------------------------------------

	this.onclose = null;
	this.opened = false;
	this.title_text = title_text;
	this.body_text = body_text;
	this.xy_size = new Object();
	this.xy_size.x = left;
	this.xy_size.y = top;
	this.xy_size.width = width;
	this.xy_size.height = height;
	this.show_mask = function(e){
		var iframe_mask = document.getElementById('iframe_mask'+idkey);

		if(e && !iframe_mask){
			var body_width = document.documentElement.scrollWidth;
			var body_height = document.documentElement.scrollHeight;
			if(document.documentElement.clientHeight>body_height) body_height = document.documentElement.clientHeight;
			iframe_mask = document.createElement("div");
			set_style(iframe_mask,iframemask_style);
			iframe_mask.id = 'iframe_mask'+idkey;
			/*iframe_mask.src = "about:blank";
			iframe_mask.setAttribute('frameborder','0',0);*/
			iframe_mask.style.width = body_width+'px';
			iframe_mask.style.height = body_height+'px';
			document.body ? document.body.appendChild(iframe_mask) : document.documentElement.appendChild(iframe_mask);
		}

		if(e){
			iframe_mask.style.display = "";
		}else if(iframe_mask){
			remove_elem(iframe_mask);
		}
		return this;
	};
	this.set_body = function(str){
		document.getElementById("owin_body"+idkey).innerHTML = str;
		this.body_text = str;
		return this;
	};
	this.set_title = function(str){
		document.getElementById("title_label"+idkey).innerHTML = str;
		this.title_text = str;
		return this;
	};
	this.set_size = function(w,h){
		if(w){
			document.getElementById("owin_body"+idkey).style.width = w>0 ? w+"px" : "auto";
			this.xy_size.width = w;
		}
		if(h){
			document.getElementById("owin_body"+idkey).style.height = h>28 ? (h-28)+"px" : "auto";
			this.xy_size.height = h;
		}
		return this;
	};
	this.get_size = function(){
		return {width:Math.max(owin.clientWidth,owin.scrollWidth),height:Math.max(owin.clientHeight,owin.scrollHeight)};
	};
	this.maxsize = function(){
		var width = document.documentElement.clientWidth;
		var height = document.documentElement.clientHeight;
		var s_left = document.documentElement.scrollLeft;
		var s_top = document.documentElement.scrollTop;

		self.set_xy(s_left,s_top);
		self.set_size(width-2,height-6);
		if(maxsize_btn){maxsize_btn.onclick = function(){self.resetsize();};}
		self.drag_win.set_ambits("disabled");
		return this;
	};
	this.resetsize = function(){
		self.set_xy(left,top);
		self.set_size(width,height);
		if(maxsize_btn){maxsize_btn.onclick = function(){self.maxsize();};}
		self.drag_win.set_ambits(false);
		return this;
	};
	this.set_xy = function(x,y){
		if(/^(\-)?[\d]+(\.[\d]+)?$/.test(x)){
			document.getElementById("owin"+idkey).style.left = x+"px";
			this.xy_size.x = x;
		}
		if(/^(\-)?[\d]+(\.[\d]+)?$/.test(y)){
			document.getElementById("owin"+idkey).style.top = y+"px";
			this.xy_size.x = y;
		}
		return this;
	};
	this.open = function(modal,closebtn,maxsizebtn){
		if(!this.opened){
			modal && this.show_mask(true);
			if(parseInt(document.getElementById("owin"+idkey).style.top)<document.documentElement.scrollTop){
				this.set_xy('null',document.documentElement.scrollTop+20);
			}
			if(closebtn){
				var close_btn_style = {position:"absolute",padding:"2px",right:"2px",top:"2px",cursor:"pointer"};
				close_btn = document.createElement("span");
				set_style(close_btn,close_btn_style);
				close_btn.innerHTML = "×";
				close_btn.onclick = typeof(closebtn)=="function" ? closebtn : self.close;
				title.appendChild(close_btn);
			}
			if(maxsizebtn){
				var maxsize_btn_style = {position:"absolute",padding:"2px",right:"2px",top:"2px",cursor:"pointer"};
				maxsize_btn = document.createElement("span");
				set_style(maxsize_btn,maxsize_btn_style);
				if(closebtn)maxsize_btn.style.right = "18px";
				maxsize_btn.innerHTML = "□";
				maxsize_btn.onclick = typeof(maxsizebtn)=="function" ? maxsizebtn : self.maxsize;
				title.appendChild(maxsize_btn);
			}
			document.getElementById("owin"+idkey).style.display = "";
		}
		this.opened = true;
		return this;
	};
	this.close = function(){
		self.opened = false;
		self.show_mask(false);
		if(typeof(self.onclose)=='function')self.onclose(self);
		remove_elem(document.getElementById("owin"+idkey));
		return this;
	};

	title_text && this.set_title(title_text);
	body_text && this.set_body(body_text);
	this.drag_win = new drag_element(title,owin);
	this.drag_win.ondrag = function(o,e,seat){
		self.xy_size.x = seat.x;
		self.xy_size.y = seat.y;
	};
}

function msg_box(mssage,title,buttons,focusbtn){
	mssage = String(mssage);
	var idkey = String(Math.random()).substr(2,10);
	var body_str = '<div style="padding:18px 20px 8px 20px;line-height:150%;">'+mssage+'</div><div style="padding:8px 0px;margin:0 8px;text-align:right;" id="msg_box_btns'+idkey+'"></div>';
	var button;
	var body_width = document.documentElement.clientWidth;
	var body_height = document.documentElement.clientHeight;
	if(!title)title = '提示信息';
	if(!focusbtn)focusbtn = 0;
	var strlen = mssage.length;
	var width = strlen>50 ? 660 : strlen<10 ? 200 : strlen*12+60;
	var height = strlen>50 ? 130 : 116;
	//alert(width+','+height);
	var msg_box_win = new createwin(width,height,body_width/2-width/2,body_height/2-height/2,title,body_str);
	msg_box_win.drag_win.set_ambits({x1:0,x2:body_width-10,y1:0,y2:body_height-10});

	if(typeof buttons == 'undefined'){
		buttons = {'OK':msg_box_win.close};
	}
	if(buttons){
		var btns_div = document.getElementById('msg_box_btns'+idkey),btn,i = 0;
		for(var lable in buttons){
			btn = document.createElement('button');
			btn.setAttribute('type','button');btn.className = 'btn';btn.style.margin = 'auto 6px';btn.innerHTML = lable;
			btn.window = msg_box_win;
			btn.onclick = buttons[lable];
			btns_div.appendChild(btn);
			if(i++==focusbtn)window.setTimeout(function(){btn.focus();},20);
		}
	}
	msg_box_win.open(true);
	msg_box_win.set_size(-1,-1);
	var size = msg_box_win.get_size(),_w = 0,_h = 0;
	if(size.width<200)_w = 200;
	else if(size.width>660)_w = 660;
	if(size.height>body_height-100)_h = body_height-100;
	if(_w || _h){
		msg_box_win.set_size(_w,_h);
		if(!_w)_w = size.width;
		if(!_h)_h = size.height;
		msg_box_win.set_xy(_w ? (body_width/2-_w/2)+document.documentElement.scrollLeft : 'noset', _h ? (body_height/2-_h/2)+document.documentElement.scrollTop : 'noset');
	}else if(size.width!=width || size.height!=height)msg_box_win.set_xy((body_width/2-size.width/2)+document.documentElement.scrollLeft, (body_height/2-size.height/2)+document.documentElement.scrollTop);
	return msg_box_win;
}

function ajax_window(e,a,title,s_features){
	var type = typeof(a);
	var myajax;
	var href = "";
	if(type=="object"){
		href = a.href ? a.href : a.attributes['href'].value;
		href = href.indexOf("?")>0 ? href+"&inajax=1" : href+"?inajax=1";
	}else if(type=="string"){
		href = a;
		href = href.indexOf("?")>0 ? href+"&inajax=1" : href+"?inajax=1";
	}
	if(href!=""){
		myajax = Ajax();
		myajax.get(href,false, function(o,r){
			var width = 550;
			var height = 400;
			var top = 50;
			var left = 100;
			if(!title)title = "View Details";
			if(s_features){
				if(s_features.width)width = s_features.width;
				if(s_features.height)height = s_features.height;
				if(s_features.top)s_features.top = s_features.top;
				if(s_features.left)s_features.left = s_features.left;
			}
			var p_window = new createwin(width,height,left,top,title,r);
			p_window.open(false,function(){p_window.close(true);},true);
		}, "html");

		doane(e);
	}
}