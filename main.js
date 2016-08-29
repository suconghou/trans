(function(w)
{
	var url='http://media.suconghou.cn/trans.php?w=';
	var cache={};
	var container;
	var d=document;

	function closest(el, selector)
	{
		var matchesFn;
		['matches','webkitMatchesSelector','mozMatchesSelector','msMatchesSelector','oMatchesSelector'].some(function(fn)
		{
			if (typeof d.body[fn] == 'function')
			{
				matchesFn = fn;
				return true;
			}
			return false;
		});
		var parent;
		while (el)
		{
			parent = el.parentElement;
			if (parent && parent[matchesFn](selector))
			{
				return parent;
			}
			el = parent;
		}
		return null;
	}

	var trans=
	{
		init:function()
		{
			var $this=this;
			container=d.querySelector('.trans');
			if(!container)
			{
				var style = ".trans{display:none;text-align:left;min-height:40px;min-width:75pt;position:absolute;max-width:5in;padding:10px 20px;line-height:1.7;box-shadow:0 0 5px #ddd;border-radius:5px;border:1px solid #b4cc83;background:#ebf4d8;margin-left:10px;margin-top:15px;font-family:lucida Grande,Verdana;font-size:9pt}.trans .tc_title{font-weight:700;font-size:1.2em}.trans .tc_type{color:#7a7a7a}";
				var s=d.createElement('style');
				s.innerHTML=style;
				d.head.appendChild(s);

				var v=d.createElement('div');
				v.className='trans';
				d.body.appendChild(v);
				container=d.querySelector('.trans');
			}
			Element.prototype.on = Element.prototype.addEventListener;
			Element.prototype.off = Element.prototype.removeEventListener;

			var handler=function(e)
			{
				if(closest(e.target,'.trans'))
				{
					return;
				}
				var funhide=function(e)
				{
					if(!closest(e.target,'.trans'))
					{
						container.style.display='none';
						d.body.off('click',funhide);
					}
				};
				var text='';
				if(d.selection&&d.selection.createRange)
				{
					text=d.selection.createRange().text;
				}
				else
				{
					var obj=w.getSelection();
					text=obj.toString();
				}
				if(/^[a-zA-Z]{2,30}$/.test(text))
				{
					$this.get(text.toLowerCase(),function(html)
					{
						container.innerHTML=html;
						container.style.top=e.pageY+'px';
						container.style.left=e.pageX+'px';
						container.style.display='block';
						d.body.on('click',funhide);
					});
				}
			};
			if(!d.body.trans)
			{
				d.body.on('dblclick',handler);
				d.body.trans=1;
			}
		},
		get:function(word,cb)
		{
			var $this=this;
			var callback=function(data)
			{
				cache[word]=data;
				var tpl=$this.genHtml(data);
				if(tpl)
				{
					cb(tpl,data);
				}
			};
			if(cache[word])
			{
				return callback(cache[word]);
			}
			this.getJSON(url+word,callback);
		},
		getJSON:function(url,callback)
		{
			var xhr = new XMLHttpRequest();
			xhr.open("GET", url);
			xhr.onload = function ()
			{
			　　callback(JSON.parse(xhr.response));
			};
			xhr.send();
		},
		genHtml:function(ret)
		{
			if(ret&&ret.symbols&&ret.symbols[0]&&ret.symbols[0].parts&&ret.symbols[0].parts.length)
			{
				var t=[];
				ret.symbols[0].parts.forEach(function(v,i)
				{
					t.push('<span>'+(i+1)+'. </span><span class="tc_type">'+v.part+' </span><span class="tc_translate">'+v.means.join('; ')+'</span>');
				});
				var tpl=
				[
					'<div class="tc_title">',
						ret.word_name,
					'</div>',
					'<div>',
						t.join('<br>'),
					'</div>'
				].join('');
				return tpl;
			}
		}
	};

	trans.init();

})(window);
