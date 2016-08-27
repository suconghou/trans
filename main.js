(function(w,$)
{
	var url='http://media.suconghou.cn/trans.php?w=';
	var cache={};
	var container;
	var trans=
	{
		init:function()
		{
			var $this=this;
			this.domInit();
			$(document).on('dblclick',function(e)
			{
				if($(e.target).closest('.trans').length)
				{
					return;
				}
				var funhide=function(e)
				{
					if(!$(e.target).closest('.trans').length)
					{
						container.hide();
						$(document).off('click',funhide);
					}
				};
				var text='';
				if(document.selection&&document.selection.createRange)
				{
					text=document.selection.createRange().text;
				}
				else
				{
					var obj=window.getSelection();
					text=obj.toString();
					if(/^[a-zA-Z]{2,30}$/.test(text))
					{
						$this.get(text.toLowerCase(),function(html)
						{
							container.html(html).css({'top':e.pageY,'left':e.pageX}).show();
							$(document).off('click',funhide).on('click',funhide);
						});
					}
				}
			});

		},
		domInit:function()
		{
			container=$('.trans');
			if(!container.length)
			{
				var style = "<style>.trans{display:none;min-height:40px;min-width:75pt;position:absolute;max-width:5in;padding:10px 20px;line-height:1.7;box-shadow:0 0 5px #ddd;border-radius:5px;border:1px solid #b4cc83;background:#ebf4d8;margin-left:10px;margin-top:15px;font-family:lucida Grande,Verdana;font-size:9pt}.trans .tc_title{font-weight:700;font-size:1.2em}.trans .tc_type{color:#7a7a7a}</style>";
				$(style).appendTo(document.head);
				$('body').append('<div class="trans"></div>');
				container=$('.trans');
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
				else
				{
					console.log(data);
				}
			};
			//do cache
			if(cache[word])
			{
				return callback(cache[word]);
			}
			$.getJSON(url+word,callback);
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

})(window,jQuery);
