jQuery(function() {
	jQuery( 'a.cps-resync' ).click( function( event ) {
		
		event.preventDefault();
		
		var reSync = {
			tableLine : "",
			progress : '',
			list : 0,
			listLength: 0,
			current: 0,
			percent: 0,
			step: 0,
			blog_id : '',
			logDiv : '',
			nonce: '',
			init : function( e, el ) {
				e.preventDefault();
				this.tableLine = el.closest( 'tr' );
				this.progress = this.tableLine.find( '.progressbar' );
				this.blog_id = this.tableLine.attr('data-blog-id');
				this.nonce = this.tableLine.find( 'input[name=_wpnonce]' ).attr( 'value' );
				this.initStep1();
			},
			initMessageSection: function() {
				this.logDiv = jQuery( '<div/>' ).addClass( 'metabox-holder has-right-sidebar' ).append( jQuery( '<div/>').addClass( this.blog_id+' stuffbox' ).append( 
						jQuery( '<h3/>' ).append( jQuery( '<label/>' ).html( this.blog_id ) ) 
					).append(
						jQuery( '<div/>' ).addClass( 'inside logBlock' )
					) );
				jQuery( '.sync_messages' ).append( this.logDiv )
			},
			setProgressBar: function( val ){
				this.progress.progressbar( {
					value: val
				});
			},
			refreshProgressBar : function(){
				this.percent = ( this.current / this.listLength ) * 100;
				this.setProgressBar( this.percent );
			},
			initStep1: function() {
				this.current = 0 ;
				this.listLength = 0 ;
				this.list = {};
				this.setProgressBar(0);
				this.initMessageSection();
				this.setMessage( 'message' , 'Step 1 !' );
				this.getTermList();
			},
			getTermList: function(){
				var _self = this; 
				jQuery.ajax({
					url:ajaxurl,
					dataType: 'json',
					type: 'POST',
					data: { action: 'cps_getTermsList', 
							nonce : _self.nonce, 
							blog_id : _self.blog_id 
					},
					beforeSend:function(){
						_self.setMessage( 'message' , 'Getting terms' );
					},
					success:function( termList ){
						//_self.setMessage( 'message' , 'Terms Getted !' );
						
						if( termList.length <= 0 ) {
							_self.setMessage( 'error', 'No terms founded !' );
							return false;
						}
		
						_self.listLength = termList.length;
						_self.list = termList;
						
						_self.mergeTerms();
					}
				});
			},
			mergeTerms: function(){
				var _self = this; 
				
				if( this.current == this.listLength ) {
					this.initStep2();
					return;
				}
				
				this.setMessage( 'message' , 'Making '+(this.current+1)+' of '+this.listLength );
				
				jQuery.ajax({
					url:ajaxurl,
					dataType: 'json',
					type: 'POST',
					data: { action: 'cps_UpdateTerm', 
							tt_id : _self.list[_self.current].tt_id, 
							term_id : _self.list[_self.current].t_id,
							taxonomy : _self.list[_self.current].taxonomy,
							blog_id : _self.blog_id,
							nonce : _self.nonce
					},
					beforeSend:function(){
						//_self.setMessage( 'message' , 'Updating Term '+(_self.current+1) );
					},
					success:function( termResult ){
						_self.setMessage( termResult.status , termResult.message );
						
						_self.current ++;
						_self.refreshProgressBar();
						_self.mergeTerms();
					}
				});
			},
			initStep2 :function(){
				this.setMessage( 'message' , 'Step 2 !' );
				this.current = 0 ;
				this.listLength = 0 ;
				this.list = {};
				this.getPostTypeEntriesList();
				
			},
			getPostTypeEntriesList: function(){
				
				var _self = this; 
				jQuery.ajax({
					url:ajaxurl,
					dataType: 'json',
					type: 'POST',
					data: { action: 'cps_getPostsList',
							blog_id : _self.blog_id,
							nonce : _self.nonce 
					},
					beforeSend:function(){
						_self.setMessage( 'message' , 'Getting objects' );
					},
					success:function( postList ){
						//_self.setMessage( 'message' , 'Peoples Getted !' );
						
						if( postList.length <= 0 ) {
							_self.setMessage( 'error', 'No objects founded !' );
							return false;
						}
		
						_self.listLength = postList.length;
						_self.list = postList;
						
						_self.mergePosts();
					}
				});
			},
			mergePosts: function(){
				var _self = this; 
				
				if( this.current == this.listLength ) {
					_self.setMessage( 'message' , 'Finish !' );
					return;
				}
				
				this.setMessage( 'message' , 'Making post '+(this.current+1)+' of '+this.listLength );
				
				jQuery.ajax({
					url:ajaxurl,
					dataType: 'json',
					type: 'POST',
					data: { action: 'cps_UpdatePost', 
							post_id : _self.list[_self.current].post_id, 
							blog_id : _self.blog_id,
							nonce : _self.nonce
					},
					beforeSend:function(){
						//_self.setMessage( 'message' , 'Updating Post '+(_self.current+1) );
					},
					success:function( postResult ){
						_self.setMessage( postResult.status , postResult.message );
						
						_self.current ++;
						_self.refreshProgressBar();
						_self.mergePosts();
					}
				});
			},
			setMessage : function( status, message ) {
				
				if( status == 'message' || status == 'success' )
					var div = jQuery( '<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"><p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span><strong> Notice :</strong> '+message+'</p></div>' );
				else
					var div = jQuery( '<div class="ui-state-error ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"><p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span><strong> Error :</strong> '+message+'</p></div>' );
				jQuery( this.logDiv ).find( '.inside' ).prepend( div );
			}
		}
		reSync.init( event, jQuery( this ) );
	} ) ;
});

