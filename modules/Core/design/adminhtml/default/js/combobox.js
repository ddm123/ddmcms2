//
// [$ID: combobox.js] Copyright (C) 2011-11-17 DDM
// http://www.jinshui8.com/
//

function combobox(options){
	if(typeof(options)!="object" || typeof(options.apply)=="undefined")return false;
	if(!options.apply || (typeof(options.apply)!="object") && empty(options.apply))return false;
	if(empty(options.data))options.data = [['','']];
	else if(typeof(options.data)=="string")options.filter = true;
	this.opt = merge({"name":"select1","defalut_text":"Select...","loading_text":"Loading...","selected":null,"method":"get","width":0,"edit":false,"filter":false},options);
	if(this.opt.method!="get")this.opt.method = "post";
	if(!this.opt.selected)this.opt.selected = this.opt.data[0];
	if(this.opt.width && !isNaN(this.opt.width))this.opt.width = (this.opt.width+20)+"px";
	var self = this,oc = true,isChanged = false,isMatch = false;
	this.hash = String(Math.random()).substr(2);
	this.item = [];
	this.origData = [];
	this.selectedIndex = 0;
	this.selectbox = null;
	this.ulList = null;
	this.resetData = false;
	this.disableChangeEvent = true;
	this.disabled = false;
	var create = function(data){
		var ul,li;
		if(ul = self.getUlList()){
			ul.innerHTML = "";
			if(ul.style.width && ul.style.width!="auto")ul.style.width = "auto";
			if(ul.style.height && ul.style.height!="auto")ul.style.height = "auto";
		}else{
			ul = $e("ul",$id("selectboxinput"+self.hash),{"onmouseout":function(){oc = true;},"onmouseover":function(){oc = false;}},{"display":""});
		}
		this.ulList = ul;
		for(var i = 0;i<data.length;i++){try{
			if(data[i]=="-"){
				self.item[i-1].className = "line";
			}else if(typeof data[i][2] == "boolean" && data[i][2]){/* disabled */
				li = $e("li",ul,{"className":"disabled","innerHTML":data[i][1]==''&&data[i][1]!==0 ? '&nbsp;' : data[i][1]});self.item[i] = li;
			}else{
				li = $e("li",ul);self.item[i] = li;
				var a = $e("a",li,{"innerHTML":data[i][1]==''&&data[i][1]!==0 ? '&nbsp;' : data[i][1],"href":"javascript:;","ul":ul,"dataIndex":i,"onclick":function(){
					self.setSelectedIndex(this.dataIndex,true);
					oc = true;
				}});
				if(self.selectedIndex==i)add_class(a,'selected');
			}}catch(exception){}
		}
		var x = moveXbySlicePos(0,$id("selectboxinput"+self.hash).parentNode);
		ul.style.left = x+"px";
		if(typeof self.opt.zIndex == "number")ul.style.zIndex = self.opt.zIndex;
		self.resize();
		self.resetPosition();
		return ul;
	};
	this.getUlList = function(){
		if(!this.ulList){
			var ul = $id("selectboxinput"+this.hash).getElementsByTagName("ul");
			if(ul.length)this.ulList = ul[0];
		}
		return this.ulList;
	};
	this.show = function(ev){
		if(self.disabled)return self;
		var datalist = self.getUlList();
		if(typeof ev == 'object' || window.event){
			if(!ev)ev = window.event;
			var target = ev.target || ev.srcElement;
			if(target.id.indexOf('selectboxbtn')===0 || (!self.opt.filter && target.id.indexOf('selectbox-input')===0)){
				if(self.origData.length && self.origData.length!=self.opt.data.length){
					self.opt.data = self.copyArray(self.origData);
					if(target.id.indexOf('selectboxbtn')===0)self.resetData = true;
				}
			}
		}
		if(self.resetData || !datalist){
			if(typeof(self.opt.data)=="string"){
				var area_ajax = Ajax();
				datalist = create([["-1",self.opt.loading_text,true]]);
				area_ajax[self.opt.method](self.opt.data,false,function(aj,result){
					if(typeof(result)!="object")result = [["-1","Results of the error",true]];
					datalist = create(result);
				},'json');
			}else{
				datalist = create(self.opt.data);
			}
		}else if(typeof self.item[self.selectedIndex] != 'undefined')add_class(self.item[self.selectedIndex].getElementsByTagName('a')[0],'selected');
		$id("selectboxbtn"+self.hash).focus();
		self.resetData = false;
		datalist.style.display = "";
		self.resetPosition();
		return self;
	};
	this.init = function(){
		this.origData = this.copyArray(this.opt.data);
		for(var i = 0;i<this.opt.data.length;i++){
			if(this.opt.data[i]!="-" && this.opt.data[i][0]==this.opt.selected[0] && this.opt.data[i][1]==this.opt.selected[1]){
				this.selectedIndex = i;
				break;
			}
		}
		if(this.selectedIndex!=i && !this.opt.edit){
			this.opt.data.unshift(this.opt.selected);
			this.origData.unshift(this.opt.selected);
		}
		this.selectbox = $e("span",typeof this.opt.apply == "string" ? $id(this.opt.apply) : this.opt.apply,{"className":"combobox-container"});
		this.selectbox.setAttribute('hash',this.hash);
		this.selectbox.innerHTML = '<span id="selectboxinput'+this.hash+'" class="selectboxinput"><input type="text" id="selectbox-input'+this.hash+'" value="'+(typeof(this.opt.selected[2])=="string" ? this.opt.selected[2] : this.opt.selected[1])+'" autocomplete="off"'+(this.opt.width?' style="width:'+this.opt.width+';"':'')+(!this.opt.filter?' class="readonly" readonly="readonly"':'')+' /><input type="hidden" name="'+this.opt.name+'" id="selectbox-hidden'+this.hash+'" value="'+this.opt.selected[0]+'" /></span><a href="javascript:;" id="selectboxbtn'+this.hash+'" class="selectboxbtn">&nbsp;</a>';
		$id("selectboxbtn"+this.hash).onclick = this.show;
		$id("selectboxbtn"+this.hash).onblur = function(){
			if(oc){self.getUlList().style.display = "none";oc = true;}
			else{window.setTimeout("$id('selectboxbtn"+self.hash+"').focus()",1);}
		};
		if(this.opt.filter){
			$id("selectbox-input"+this.hash).onchange = function(){
				if(!oc)return false;
				isMatch = false;
				for(var i = 0;i<self.opt.data.length;i++){
					if(this.value==self.opt.data[i][1] || (self.opt.data[i][2] && this.value===self.opt.data[i][2])){
						if(typeof self.opt.data[i][2] == "boolean" && self.opt.data[i][2]){
							$id("selectbox-hidden"+self.hash).value = "";
							this.value = "";
							//self.selectedIndex = -1;
							break;
						}else{
							$id("selectbox-hidden"+self.hash).value = self.opt.data[i][0];
							self.setSelectedIndex(i);
							isMatch = true;
							if(typeof self.opt.change=="function")self.opt.change(self,self.opt.data[i]);
							return self;
						}
					}
				}
				if(self.opt.edit){
					$id("selectbox-hidden"+self.hash).value = this.value;
					self.selectedIndex = -1;
				}else{
					if(self.opt.data.length!=self.origData.length){
						self.opt.data = self.copyArray(self.origData);
						self.resetData = true;
					}
					self.setSelectedIndex(self.selectedIndex);
				}
				isChanged = true;
				return true;
			};
			$id("selectbox-input"+this.hash).onfocus = function(){
				if(this.value==self.opt.defalut_text)this.value = '';
				this.select();
				self.disableChangeEvent = false;
			};
			$id("selectbox-input"+this.hash).onblur = function(){
				var ul =self.getUlList();
				if(this.value=="" && !isMatch){
					if(oc && !self.opt.edit){self.setSelectedIndex(self.selectedIndex,true);}
					if(!isChanged && self.opt.data.length!=self.origData.length){self.opt.data = self.copyArray(self.origData);self.resetData = true;}
				}
				if(oc && ul && ul.style.display==""){ul.style.display = "none";oc = true;}
				isChanged = false;
			};
			var keyUpEventName = window.navigator.userAgent.indexOf("MSIE")!=-1 ? 'propertychange' : 'input';
			var iTimerID;
			observer($id("selectbox-input"+this.hash),keyUpEventName,function(ev){
				if(self.disableChangeEvent || self.disabled)return;
				var input = ev.target || window.event.srcElement;
				if(typeof(self.opt.data)=="string"){
					if(input.value!=""){
						if(iTimerID){ window.clearTimeout(iTimerID);iTimerID = null; }
						iTimerID = window.setTimeout(function(){
							var $data = self.opt.data.replace(/(?:\?|&)key=(?:[^&]|$)/,'');
							$data += ($data.indexOf("?") ? "&" : "?")+"key="+encodeURIComponent(input.value);
							self.setData($data).show();
						},1000);
					}
				}else{
					var len = self.origData.length,ul,i,j,k = 0;
					if(input.value!=""){
						self.opt.data = [];
						for(i=0;i<len;i++){
							if(self.origData[i]=="-" || self.origData[i][1].indexOf(input.value)>=0 || (typeof self.origData[i][2]=="string" && self.origData[i][2].indexOf(input.value)>=0)){
								if(self.origData[i]=="-")self.opt.data[k] = "-";
								else{
									self.opt.data[k] = [];
									for(j=0;j<self.origData[i].length;j++)self.opt.data[k][j] = self.origData[i][j];
								}
								k++;
							}
						}
					}
					var newDatalen = self.opt.data.length;
					if(input.value=="" || newDatalen){
						if(input.value=="" && len!=newDatalen)self.opt.data = self.copyArray(self.origData);
						ul = create(self.opt.data);
						if(ul.style.display=="none")ul.style.display = "";
						self.resetPosition();
						/*self.selectedIndex = -1;*/
					}else{
						if((ul = self.getUlList()) && ul.style.display==""){ul.style.display = "none";oc = true;}
					}
				}
			});
		}else{
			$id("selectbox-input"+this.hash).onclick = this.show;
		}
		if(self.opt.disabled)self.disable(true);
		return self;
	};
}
combobox.prototype = {
	getSelected:function(){
		return this.opt.data[this.selectedIndex] ? this.opt.data[this.selectedIndex] : false;
	},
	getHash:function(){
		return this.hash;
	},
	setData:function(data){
		this.opt.data = data;
		this.resetData = true;
		return this;
	},
	setSelectedIndex:function(index,dc){
		var ul;
		if(typeof this.item[this.selectedIndex] != 'undefined')remove_class(this.item[this.selectedIndex].getElementsByTagName('a')[0],'selected');
		this.disableChangeEvent = typeof dc == "undefined" ? true : dc;
		if(this.opt.data[index] && (this.selectedIndex!=index || this.opt.data.length!=this.origData.length)){
			this.selectedIndex = index;
			$id("selectbox-hidden"+this.hash).value = this.opt.data[index][0];
			$id("selectbox-input"+this.hash).value = typeof(this.opt.data[index][2])=="string" ? this.opt.data[index][2] : this.opt.data[index][1];
			if(typeof this.opt.change=="function")this.opt.change(this,this.opt.data[index]);
			if(this.opt.data.length!=this.origData.length){
				for(var i = this.origData.length;i--;){
					if(this.origData[i]!="-" && this.opt.data[index]!="-" && this.opt.data[index][0]==this.origData[i][0] && this.opt.data[index][1]==this.origData[i][1]){
						this.selectedIndex = i;break;
					}
				}
				this.opt.data = this.copyArray(this.origData);this.resetData = true;
			}
		}else if(this.selectedIndex>=0 && this.opt.data[this.selectedIndex]){
			$id("selectbox-hidden"+this.hash).value = this.opt.data[this.selectedIndex][0];
			$id("selectbox-input"+this.hash).value = typeof(this.opt.data[this.selectedIndex][2])=="string" ? this.opt.data[this.selectedIndex][2] : this.opt.data[this.selectedIndex][1];
		}
		if((ul = this.getUlList()) && ul.style.display==""){ul.style.display = "none";oc = true;}
		return this;
	},
	setSelectedValue:function(value,dc){
		if(typeof dc == "undefined")dc = true;
		for(var index = 0,len = this.opt.data.length;len--;){
			if(this.opt.data[len][0]+''===value+''){
				index = len;
				break;
			}
		}
		return this.setSelectedIndex(index,dc);
	},
	add:function(rowData){
		if(rowData && typeof(this.opt.data)=="object"){
			if(rowData=="-"){
				if(this.item.length)this.item[this.item.length-1].className = "line";
				this.item.push(undefined);
			}else{
				var ul = this.getUlList();
				if(!ul){
					this.show();ul = this.getUlList();
					ul.style.display = "none";oc = true;
				}
				if(typeof rowData[2] == "boolean" && rowData[2]){/* disabled */
					var li = $e("li",ul,{"className":"disabled","innerHTML":rowData[1]==''&&rowData[1]!==0 ? '&nbsp;' : rowData[1]});this.item.push(li);
				}else{
					var self = this,li = $e("li",ul);this.item.push(li);
					var a = $e("a",li,{"innerHTML":rowData[1]==''&&rowData[1]!==0 ? '&nbsp;' : rowData[1],"href":"javascript:;","ul":ul,"dataIndex":this.opt.data.length,"onclick":function(){
						self.setSelectedIndex(this.dataIndex,true);
						oc = true;
					}});
				}
				this.resize();
			}
			this.opt.data.push(rowData);
			this.origData.push(rowData);
		}
		return this;
	},
	remove:function(index){
		var removed = false,ul;
		if(this.opt.data[index]){this.opt.data.splice(index,1);this.origData.splice(index,1);removed = true;}
		if(this.item[index]){
			$e_(this.item[index]);
			this.item.splice(index,1);
			removed = true;
		}
		if(removed && (ul = this.getUlList())){
			var a = ul.getElementsByTagName("a"),len = a.length,i;
			for(i = index;i<len;i++)a[i].dataIndex--;
			this.resize();
		}
		return this;
	},
	disable:function(flag){
		this.disabled = typeof flag == 'undefined' ? true : !!flag;
		if(this.disabled)add_class($id("selectboxinput"+this.hash).parentNode,'disabled');
		else remove_class($id("selectboxinput"+this.hash).parentNode,'disabled');
		$id("selectbox-input"+this.hash).disabled = this.disabled;
		$id("selectbox-hidden"+this.hash).disabled = this.disabled;
		return this;
	},
	resize:function(){
		var ul = this.getUlList(),isDisplay;
		if(ul){
			if(ul.style.width && ul.style.width!="auto")ul.style.width = "auto";
			if(ul.style.height && ul.style.height!="auto")ul.style.height = "auto";
			isDisplay = ul.style.display=="";
			if(!isDisplay)ul.style.display = "";
			var listWidth = element_width(ul),listHeight = element_height(ul),w = element_width($id("selectboxinput"+this.hash).parentNode);
			var bodyHeight = document.documentElement.clientHeight || document.body.clientHeight;
			if(!isDisplay){ul.style.display = "none";oc = true;}
			if(listWidth<w)ul.style.width = (w-1)+"px";
			if(listHeight>=bodyHeight){
				ul.style.height = (bodyHeight-20)+"px";
				if(listWidth>=w)ul.style.width = (listWidth+20)+"px";
			}
		}
		return this;
	},
	resetPosition:function(){
		var ul = this.getUlList();
		if(ul){
			if(ul.style.height && ul.style.height!="auto")ul.style.height = "auto";
			var y = moveYbySlicePos(24,$id("selectboxinput"+this.hash).parentNode),h = ul.clientHeight;
			var x,w;
			var bodyScrollTop = document.documentElement.scrollTop || document.body.scrollTop;
			var bodyScrollLeft = document.documentElement.scrollLeft || document.body.scrollLeft;
			var bodyHeight = document.documentElement.clientHeight || document.body.clientHeight;
			var bodyWidth = document.documentElement.clientWidth || document.body.clientWidth;
			var ry = y - bodyScrollTop;
			if(y+h>bodyScrollTop+bodyHeight){
				var h2 = bodyHeight/2;
				var ah = Math.max(ry,bodyHeight-ry)-32;/* Allowed height */
				if(h>ah){
					h = ah;
					ul.style.height = ah+"px";
					if(ul.clientWidth<ul.scrollWidth)ul.style.width = (element_width(ul)+20)+"px";
				}
				if(ry>h2)y = y-26-h;
			}
			x = moveXbySlicePos(0,$id("selectboxinput"+this.hash).parentNode);
			w = parseInt(ul.style.width) || element_width(ul);
			if(x+w>bodyScrollLeft+bodyWidth){
				x -= x+w-bodyScrollLeft-bodyWidth+8;
			}
			ul.style.left = x+"px";
			ul.style.top = y+"px";
		}
		return this;
	},
	destroy:function(){
		$e_(this.selectbox);
		this.item = [];
		this.origData = [];
		this.selectedIndex = 0;
		this.selectbox = null;
		this.ulList = null;
		this.resetData = false;
		this.disableChangeEvent = true;
		this.disabled = false;
		return this;
	},
	copyArray:function(source){
		var a = source.constructor === Array ? [] : {};
		for(var key in source){
			a[key] = typeof source[key] == "object" ? this.copyArray(source[key]) : source[key];
		}
		return a;
	}
};