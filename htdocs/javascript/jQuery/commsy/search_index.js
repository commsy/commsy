jQuery(document).ready(function() {
	var Indexer = {
		numManager: null,
		numComplete: 0,
		numItems: 0,
		numStep: 10000,
			
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
				break;
				case 'truncate':
					this.truncate();
				break;
				case 'indexing':
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
			// bar
			jQuery('<div/>', {
				style: 'border: 2px solid LightSlateGrey; display: none;'
			})
				.append(jQuery('<div/>', {
					style: 'background-color: BlanchedAlmond; width: 0%;',
					id: 'indexing_bar'
				})).appendTo(jQuery('div[id="indexing_status"]'));
			
			// start button
			jQuery('<input/>', {
				value: "Start",
				type: "Button",
				id: "indexing_start"
			}).appendTo(jQuery('div[id="indexing_status"]'));
			
			jQuery('input[id="indexing_start"]').click(function() {
				jQuery('div[id="indexing_bar"]').parent().css('display', 'block');
				Indexer.process('truncate');
			});
		},
		
		truncate: function(manager) {
			var json_data = new Object();
			json_data['do'] = 'truncate';
			json_data['manager'] = manager;
			
			jQuery('div[id="indexing_bar"]').text('Indexing...');
			
			jQuery.ajax({
				url: 'commsy.php?cid=' + getURLParam('cid') + '&mod=ajax&fct=search_index&output=json',
				data: json_data,
				success: function(data) {
					var response = jQuery.parseJSON(data);
			   		if(response) {
			   			if(response.status == 'done') {
			   				console.log('completed');
			   				Indexer.process('indexing');
			   			}
			   		}
				}
			});
		},
		
		indexing: function(manager) {
			this.numItems = 0;
			
			var json_data = new Object();
			json_data['do'] = 'getNumItems';
			json_data['manager'] = manager;
			
			// get number of items
			jQuery.ajax({
				url: 'commsy.php?cid=' + getURLParam('cid') + '&mod=ajax&fct=search_index&output=json',
				data: json_data,
				success: function(data) {
					var response = jQuery.parseJSON(data);
			   		if(response) {
			   			Indexer.numItems = response.number;
			   			console.log(Indexer.numItems);
			   			
			   			for(i=0; i <= Indexer.numItems; i+=Indexer.numStep) {
							json_data = new Object();
							json_data['do'] = 'index';
							json_data['manager'] = manager;
							json_data['offset'] = Indexer.numStep * i;
							json_data['limit'] = Indexer.numStep;
							jQuery.ajax({
								url: 'commsy.php?cid=' + getURLParam('cid') + '&mod=ajax&fct=search_index&output=json',
								data: json_data,
								success: function(data) {
									var response = jQuery.parseJSON(data);
							   		if(response) {
							   			if(response.status == 'done') {
							   				Indexer.numComplete += 1;
							   				
							   				console.log('completed');
								   			
							   				if(response.processed >= Indexer.numItems) {
							   					// update bar
									   			var percent = Indexer.numComplete * 100 / Indexer.numManager;
									   			jQuery('div[id="indexing_bar"]').css('width', percent+'%');
									   			
									   			if(percent == 100) {
									   				jQuery('div[id="indexing_bar"]').text('Complete');
									   				Indexer.numComplete = 0;
									   			} else {
									   				if(Indexer.numComplete < Indexer.numManager) {
										   				Indexer.indexing(manager+1);
										   			}
									   			}
							   				}
							   			}
							   		}
								}
							});
						}
			   		}
				}
			});
		}
	};
	
	Indexer.init();
});