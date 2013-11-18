window.addEvent('domready', function() {
	var conBody = $('conBody');
	var req = new Request({
		'url': './README.md',
		'method': 'GET',
		'onSuccess': function(res) {
			var markdown = new Showdown.converter({extensions: ['table']});
			conBody.set('html', markdown.makeHtml(res));
			conBody.getElements('pre').each(function(pre) {
				var code = pre.getElement('code').get('html');
				var syntax = pre.getElement('code').get('class');
				pre.set('html', code);
				switch (syntax) {
					case 'xml':
						pre.set('class', 'brush: xml');
						break;
					case 'php':
						pre.set('class', 'brush: php');
						break;
					default:
						pre.set('class', 'brush: plain');
						break;
				}
			});
			conBody.getElements('table').each(function(table) {
				table.addClass('table');
			});

			SyntaxHighlighter.highlight();
		},
		'onFailure': function() {
			conBody.set('html', '<div class="alert alert-danger">Could not open README.md - Sorry!');
		}
	});
	req.send();
});
