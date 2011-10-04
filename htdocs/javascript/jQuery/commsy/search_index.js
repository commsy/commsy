jQuery(document).ready(function() {
	var Indexer = {
		numManager: null,
		numComplete: 0,
			
		/* init function */
		init: function() {
			this.process('num');
		},
		
		process: function(todo) {
			switch(todo) {
				case 'num':
					this.getNumManagers();
				break;
				case 'initGUI':
					this.initGUI();
					this.indexing(0);
				break;
			}
		},
		
		getNumManagers: function() {
			var json_data = new Object();
			json_data['do'] = 'getNumManagers';
			
			jQuery.ajax({
				url: 'commsy.php?cid=' + getURLParam('cid') + '&mod=ajax&fct=search_index&output=json',
				data: json_data,
				success: function(data) {
					var response = jQuery.parseJSON(data);
			   		if(response) {
			   			Indexer.numManager = response.number;
			   			Indexer.process('initGUI');
			   		}
				}
			});
		},
		
		initGUI: function() {
			jQuery('<div/>', {
				style: 'border: 2px solid LightSlateGrey;'
			})
				.append(jQuery('<div/>', {
					style: 'background-color: BlanchedAlmond; width: 0%;',
					text: 'Indexing...',
					id: 'indexing_bar'
				})).appendTo(jQuery('div[id="indexing_status"]'));
		},
		
		indexing: function(manager) {
			var json_data = new Object();
			json_data['do'] = 'index';
			json_data['manager'] = manager;
			
			jQuery.ajax({
				url: 'commsy.php?cid=' + getURLParam('cid') + '&mod=ajax&fct=search_index&output=json',
				data: json_data,
				success: function(data) {
					var response = jQuery.parseJSON(data);
			   		if(response) {
			   			if(response.status == 'done') {
			   				Indexer.numComplete += 1;
			   				
			   				console.log('completed');
				   			
				   			// update bar
				   			var percent = Indexer.numComplete * 100 / Indexer.numManager;
				   			jQuery('div[id="indexing_bar"]').css('width', percent+'%');
				   			
				   			if(Indexer.numComplete < Indexer.numManager) {
				   				Indexer.indexing(manager+1);
				   			}
			   			}
			   		}
				}
			});
		}
	};
	
	Indexer.init();
});