/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
var config = {
	map: {
		'*': {
			markerwithlabel: 'Lof_StoreLocator/js/libs/markerwithlabel',
			storelocator: 'Lof_StoreLocator/js/plugins/storelocator/jquery.storelocator',
			handlebars: 'Lof_StoreLocator/js/libs/handlebars.min',
			markerclusterer: 'Lof_StoreLocator/js/libs/markerclusterer.min',
			bootstrap: 'Lof_StoreLocator/js/libs/bootstrap.min'
		}
	},
	shim: {
    	'storelocator': {
            deps: ['handlebars', 'jquery']
        },
        'Lof_StoreLocator/js/plugins/storelocator/jquery.storelocator': {
        	deps: ['handlebars', 'jquery']	
        },
        'Lof_StoreLocator/js/libs/bootstrap.min': {
            deps: ['jquery']
        }
    }
};