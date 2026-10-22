function showSubMemus(o,m,x,y){
	var li = o, ul = li.getElementsByTagName("ul");
	ul = ul.length ? ul[0] : false;
	if(m && ul){
		ul.style.left = moveXbySlicePos(isNaN(x)?0:x,li)+"px";
		ul.style.top = "34px";
		ul.style.display = "block";
		if(is_ie){
			var iframe = document.getElementById("layer_iframe");
			if(iframe){
				iframe.style.left = ul.style.left;
				iframe.style.top = ul.style.top;
				iframe.style.width = ul.offsetWidth + 'px';
				iframe.style.height = ul.offsetHeight + 'px';
				iframe.style.visibility = "visible";
			}else{
				createIframeLayer(ul);
			}
		}
	}else if(ul){
		ul.style.display = "none";
		if(is_ie && document.getElementById("layer_iframe")){
			document.getElementById("layer_iframe").style.visibility = "hidden";
		}
	}
}
function createIframeLayer(menu){var layer = document.createElement('iframe');layer.tabIndex = '-1';layer.src = 'javascript:""';layer.id = 'layer_iframe';layer.frameBorder = '0';layer.scrolling = 'no';layer.style.position = 'absolute';layer.style.zIndex = 1;layer.style.left = menu.style.left;layer.style.top = menu.style.top;layer.style.filter = 'alpha(opacity=0)';layer.style.MozOpacity = 0;layer.style.opacity = 0;layer.style.width = menu.offsetWidth + 'px';layer.style.height = menu.offsetHeight + 'px';menu.parentNode.appendChild(layer);return layer;}
function initMenus(){
	var ul = $id("__menus__").getElementsByTagName("ul"),ol = $id("admin-user-menus").parentNode.getElementsByTagName("ol"),overol = false;
	var uw,lw;
	if(ul && ul.length){
		var menuList = ul[0].getElementsByTagName("li"),currentActive = false;
		for(var i = menuList.length; i--;){
			if(menuList[i].parentNode==ul[0]){
				var submenus = menuList[i].getElementsByTagName("ul");
				if(submenus.length){
					menuList[i].onmouseover = function(){showSubMemus(this,true,0,0);};
					menuList[i].onmouseout = function(){showSubMemus(this,false,0,0);};
				}
			}
		}
	}
	$id("admin-user-menus").onclick = function(){
		ol[0].style.display = "block";
		if(!uw){
			uw = element_width($id("admin-user-menus"));
			lw = element_width(ol[0]);
			if(uw>lw){
				ol[0].style.width = uw+"px";
				ol[0].style.borderRadius = "0px 0px 3px 3px";
			}
		}
	};
	$id("admin-user-menus").onblur = function(){if(!overol)ol[0].style.display = "none";};
	ol[0].onmouseover = function(){overol = true;};
	ol[0].onmouseout = function(){overol = false;};
}
function saveAndContinueEdit(o){
	if(o.validate()){
		if(typeof o.onsuccess == 'function')o.onsuccess(o.form,null,o);
		if(o.validateResult){
			var act = o.form.action;
			o.form.action += (act.indexOf('?')>-1 ? '&' : '?')+'back=edit';
			o.form.submit();
			return true;
		}
	}
	return false;
}
function Grid(gridFormId,searchGridFormId){
	this.gridFormId = typeof gridFormId == 'undefined' ? null : gridFormId;
	this.searchGridFormId = typeof searchGridFormId == 'undefined' ? null : searchGridFormId;
	this.getFieldVUrl = '';
	this.saveFieldVUrl = '';
	this.ajaxRequesting = false;
	this.modifying = false;
	this.disableSelected = false;
}
Grid.prototype = {
	setRowsClickEvent:function(c){
		var len, tr,tds, checkboxs = document.getElementsByName(c),self = this;
		if(len = checkboxs.length){
			for(var i = 0; i < len; i++){
				if(!checkboxs[i].disabled){
					tr = checkboxs[i].parentNode.parentNode;tr.checkbox = checkboxs[i];
					tr.onclick = function(){
						if(!self.disableSelected && !self.modifying){
							if(this.checkbox.checked){
								this.checkbox.checked = false;remove_class(this,"selected");
							}else{
								this.checkbox.checked = true;add_class(this,"selected");
							}
						}
					};
					tds = $tag('td',tr,false);
					for(var j = tds.length;j--;){
						tds[j].onmouseover = function(){
							self.disableSelected = (this.getElementsByTagName('a').length || this.getElementsByTagName('input').length) ? true : false;
						};
					}
				}
			}
		}
		return this;
	},
	activateSort:function(){
		var sorturls = document.getElementById("transactionTable"),sort,$sort;
		if(sorturls){
			sorturls = sorturls.getElementsByTagName("thead")[0].getElementsByTagName("th");
			for(var i = 0; sorturls[i]; i++){
				sort = sorturls[i].getAttribute("sort");
				if(sort && sort!=""){
					sorturls[i].onclick = function(){
						var currentUrl = window.location.href,sort = this.getAttribute("sort").split(',',2);
						currentUrl = currentUrl.replace(/&?(?:col|dir)=[^&]*/ig,'').replace(/(?:\?|&)$/,'');
						window.location.href = currentUrl+(currentUrl.indexOf('?')>0 ? '&' : '?')+'col='+sort[0]+'&dir='+(typeof sort[1] == 'undefined' || sort[1]=='' || sort[1].toUpperCase()=='DESC' ? 'ASC' : 'DESC');
					};
					$sort = sort.split(',',2);
					if(typeof $sort[1] != 'undefined' && $sort[1]!=''){
						add_class(sorturls[i],$sort[1].toUpperCase()=='DESC' ? 'sort_desc' : 'sort_asc');
					}
				}
			}
		}
		return this;
	},
	selectAllRows:function(e,c){
		var tr, checkbox, i = 0, checkboxs = document.getElementsByName(c);
		if (checkboxs.length) {
			while(checkbox = checkboxs[i++]){
				if(!checkbox.disabled){
					tr = checkbox.parentNode.parentNode;
					if(e.checked){
						checkbox.checked = true;add_class(tr, "selected");
					}else{
						checkbox.checked = false;remove_class(tr, "selected");
					}
				}
			}
		}
	},
	activateModify:function(){
		var rows = document.getElementById("transactionTable"),self = this,modify;
		if(rows){
			rows = rows.getElementsByTagName("tbody")[0].getElementsByTagName("td");
			for(var i = 0; rows[i]; i++){
				modify = rows[i].getAttribute("modify");
				if(modify && modify!=""){
					rows[i].htmlS = rows[i].innerHTML;
					rows[i].ondblclick = function(){
						if(!self.modifying){
							var html = this.innerHTML,tdw = element_width(this,0);
							this.modifyData = this.getAttribute("modify").split('|');
							this.innerHTML = '&nbsp;';
							if(self.getFieldVUrl==''){
								self._showInput(this,html,html,tdw);
							}else if(!self.ajaxRequesting){
								var td = this;
								add_class(this,'loading');
								self.ajaxRequesting = true;
								Ajax().post(self.getFieldVUrl,{'f':this.modifyData[0],'id':this.modifyData[2],'v':html},function(XHR,result){
									self._showInput(td,result,html,tdw);
									remove_class(td,'loading');
									self.ajaxRequesting = false;
								},'html');
							}
						}
					};
				}
			}
		}
		return this;
	},
	activateSearchForm:function(){
		var searchForm;
		if(this.searchGridFormId && (searchForm = $id(this.searchGridFormId))){
			searchForm.noticeDir = 'up';
			validateForm(searchForm,function(form,event){
				doane(event);
				disabled(form.parentNode.parentNode,true,'Loading');
				var url = form.action;
				url += url.indexOf('?')>0 ? '&' : '?';
				url += getFormElements(form,true);
				window.location.href = url;
			},'up');
		}
		return this;
	},
	_showInput:function(e,v,ov,w){
		var self = this,n = null;
		var ev = function(){if(n)$e_(n);unobserver(document.body,'mouseup',ev);};
		this.modifying = true;
		switch(e.modifyData[1]){
			case 'bool':
				if(/class=["']?[^"']*icon\b/i.test(v))v = /class=["']?[^"']*icon-ok\b/i.test(v) ? 1 : 0;
				var elm = $e('input',e,{'type':'checkbox','value':'1','checked':!empty(v),'onblur':function(){
					if(this.checked==!empty(v)){e.innerHTML = ov;self.modifying = false;}
					else{
						add_class(e,'loading');
						self.ajaxRequesting = true;
						e.innerHTML = '&nbsp;';
						Ajax().post(self.saveFieldVUrl==''?window.location.href:self.saveFieldVUrl,{'f':e.modifyData[0],'id':e.modifyData[2],'v':this.checked?1:0},function(XHR,result){
							if(!result.success && result.message){
								n = $notice(e,{'text':result.message,'icon':'error'});
								window.setTimeout(function(){observer(document.body,'mouseup',ev);},1000);
							}
							e.innerHTML = result.value=='1' ? '<i class="icon icon-ok"></i>' : '<i class="icon icon-remove"></i>';
							self.modifying = false;self.ajaxRequesting = false;remove_class(e,'loading');
						},'json');
					}
				},'onkeydown':function(event){
					var e = window.event || event;
					if(e.keyCode==13){
						doane(e);this.blur();
					}else if(e.keyCode == 27){
						this.checked = !empty(v);this.blur();
					}
				}});
				elm.focus();
				break;
			default:
				var elm = $e('input',e,{'type':'text','className':'text','value':v,'onblur':function(){
					if(this.value==v){e.innerHTML = ov;self.modifying = false;}
					else{
						add_class(e,'loading');
						self.ajaxRequesting = true;
						e.innerHTML = '&nbsp;';
						Ajax().post(self.saveFieldVUrl==''?window.location.href:self.saveFieldVUrl,{'f':e.modifyData[0],'id':e.modifyData[2],'v':this.value},function(XHR,result){
							if(!result.success && result.message){
								n = $notice(e,{'text':result.message,'icon':'error'});
								window.setTimeout(function(){observer(document.body,'mouseup',ev);},1000);
							}
							e.innerHTML = result.value;self.modifying = false;self.ajaxRequesting = false;remove_class(e,'loading');
						},'json');
					}
				},'onkeydown':function(event){
					var e = window.event || event;
					if(e.keyCode==13){
						doane(e);this.blur();
					}else if(e.keyCode == 27){
						this.value = v;this.blur();
					}
				}},{'width':w+'px'});
				var v = elm.value;
				elm.focus();elm.select();
		}
		return this;
	}
};
function AdminGroup(divId){
	var self = this;
	this.container = $id(divId);
	this.checkboxs = [];
	this.init = function(){
		this.container.style.position = 'relative';
		this.checkboxs = $tag('input',this.container,true);
		for(var i = this.checkboxs.length;i--;){
			this.checkboxs[i].onclick = this.setGroup;
		}
		return this;
	};
	this.setGroup = function(){
		if(self.getSelectedContainer(false))$e_(self.getSelectedContainer(false));
		var itemIDS = [];
		for(var i = self.checkboxs.length;i--;){
			if(self.checkboxs[i].checked){
				$e('li',self.getSelectedContainer(true),{'_checkbox':self.checkboxs[i],'innerHTML':'<i class="icon icon-ok"></i> <span>'+self.checkboxs[i].getAttribute('title')+'</span>'}).setAttribute('itemid',self.checkboxs[i].value);
				itemIDS.push(self.checkboxs[i].value);
			}
		}
		if(itemIDS.length>1){
			itemIDS = itemIDS.sort(function(a,b){return a-b;});
			ToolMan.dragsort().makeListSortable(self.getSelectedContainer(),_setPosition);
			ToolMan.junkdrawer().restoreListOrder(self.getSelectedContainer().id,itemIDS);
		}
		return self;
	};
	this.getSelectedContainer = function(create){
		var ct= $id(this.container.id+'-selected');
		if(typeof create == 'undefined')create = true;
		if(!ct && create){
			ct = $e('ul',this.container,{'id':this.container.id+'-selected','className':'selected-group-list'});
			ct.onmouseover = function(){if(this.getElementsByTagName('li').length>1)$notice(this,{text:'可以上下拖动调整权限判断优先顺序',direction:'left'});};
			ct.onmouseout = function(){$notice(this);};
		}
		return ct;
	};
	var _setPosition = function(item){
		item.toolManDragGroup.register('dragend',function(){
			var list = $tag('li',self.getSelectedContainer(),false),l = list.length;
			for(var i = 0;i<l;i++){
				list[i]._checkbox.value = i+1;
			}
		});
	};
	if(this.container)this.init();
}
function RadioGroup(id){
	if(!id || !document.getElementById(id))return;
	this.container = document.getElementById(id);
	this.radios = [];
	this.labels = [];
	this.disabled = false;
	var self = this;
	var _labels = $tag('label',this.container);
	var _labelsCount = _labels.length;
	if(_labelsCount){
		for(var i = 0,_for,_radio;i<_labelsCount;i++){
			_for = _labels[i].getAttribute('radio_id');
			if(_for && (_radio = document.getElementById(_for))){
				this.radios.push(_radio);
				this.labels.push(_labels[i]);
				_radio.onclick = function(){self.setChecked(this);};
			}
		}
	}
}
RadioGroup.prototype = {
	init:function(){
		for(var i = this.radios.length;i--;){
			if(this.radios[i].checked){
				this.setChecked(this.radios[i]);
				break;
			}
		}
		return this;
	},
	setChecked:function(radio,checked){
		if(typeof checked != 'undefined')radio.checked = checked;
		for(var i = this.labels.length;i--;){
			if(radio.checked && radio.id==this.labels[i].getAttribute('radio_id'))add_class(this.labels[i],'checked');
			else remove_class(this.labels[i],'checked');
		}
		return this;
	},
	disable:function(flag){
		this.disabled = typeof flag == 'undefined' ? true : !!flag;
		for(var i = this.labels.length;i--;){
			if(this.disabled)add_class(this.labels[i],'disabled');
			else remove_class(this.labels[i],'disabled');
			this.radios[i].disabled = this.disabled;
		}
		return this;
	},
	setValue:function(value){
		for(var i = this.radios.length;i--;){
			if(this.radios[i].value===value+''){
				this.setChecked(this.radios[i],true);
				break;
			}
		}
		return this;
	}
};
function setUseDefaultValue(c,id,type){
	if(typeof type == 'undefined')type = 'input';
	var elm;
	switch(type){
		case 'select':
			elm = id.replace(/[^A-Za-z]([A-Za-z])/g,function($0,$1){return $1.toUpperCase();});
			eval("elm = typeof "+elm+" == 'undefined' ? false : "+elm+"");
			elm.disable(c.checked);
			if(typeof elm.opt.default_value != "undefined")elm.setSelectedValue(elm.opt.default_value);
			break;
		case 'radiogroup':
			elm = id.replace(/[^A-Za-z]([A-Za-z])/g,function($0,$1){return $1.toUpperCase();});
			eval("elm = typeof "+elm+" == 'undefined' ? false : "+elm+"");
			elm.disable(c.checked);
			if(c.checked && elm.container.getAttribute('default_value')!=null)elm.setValue(elm.container.getAttribute('default_value'));
			break;
		case 'editor':
			elm = id.replace(/[^A-Za-z]([A-Za-z])/g,function($0,$1){return $1.toUpperCase();});
			eval("elm = typeof "+elm+" == 'undefined' ? false : "+elm+"");
			if(elm)elm.setReadOnly(c.checked);
			break;
		case 'textarea':
			if(document.getElementById('is_use_editor_'+id) && document.getElementById('is_use_editor_'+id).checked){
				setUseDefaultValue(c,id,'editor');
			}
			setUseDefaultValue(c,id,'text');
			break;
		default:
			elm = $id(id);
			elm.disabled = c.checked;
			if(elm.disabled && elm.getAttribute('default_value')!=null)elm.value = elm.getAttribute('default_value');
	}
}
function isDisplayEditor(checkBox,id,varName,config){
	if(typeof CKEDITOR != "undefined"){
		if(checkBox.checked){
			if(typeof config != "object")config = {};
			config.allowedContent = true;
			window[varName] = CKEDITOR.replace(id,config);
			if(typeof CKFinder != "undefined"){
				CKFinder.setupCKEditor(window[varName],{"basePath":BASE_URL+"ckeditor/ckfinder/"});
			}
			checkBox.origVerify = $id(id).getAttribute('verify');
			$id(id).setAttribute('verify','');
		}else if(window[varName]){
			window[varName].updateElement();
			window[varName].destroy();
			if(typeof checkBox.origVerify != "undefined")$id(id).setAttribute('verify',checkBox.origVerify);
		}
	}else{
		window.alert('你没有安装CKEditor');
		checkBox.checked = false;
	}
}
