import { Component, Fragment, render } from '@wordpress/element';
import { Modal, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * functions
 */

const isValidHttpURL = ( siteURL ) => {
    let testURL;
    try {
        testURL = new URL(siteURL);
    } catch (_) {
        return false;
    }

    return testURL.protocol === "http:" || testURL.protocol === "https:";

}
const fetchCategories = () => {
    const siteURLInput = document.querySelector( 'input[name="_partners_sites_site_url"]' );
    let URL = siteURLInput.value;
    let categories = [];
    const selectField = document.getElementById('_partners_sites_remote_category' );
    const hiddenValueField = document.getElementById('_partners_sites_remote_category_value' );
    const selectedValueId = hiddenValueField.value;
    let totalPages = 1;
    const langField = document.getElementById('_partners_sites_remote_lang' );
    const langValue = langField ? langField.value : 'none';

    selectField.innerHTML = '';

    if ( ! isValidHttpURL( URL ) ) {
        return;
    }
    if ( URL.substr(URL.length - 1) == '/' ) {
        URL = URL.slice(0, -1);
    }
    URL = URL + '/wp-json/wp/v2/categories/?per_page=100';
    if ( langValue !== 'none' ) {
        URL = URL + '&lang=' + langValue;
    }
    fetch( URL )
    .then(response => {
        totalPages = response.headers.get('X-WP-TotalPages');
        return response.json()
    })
    .then( data => {
        const opt = document.createElement('option');
        opt.value = '';
        opt.innerHTML = __( 'All Categories', 'jeo-mps' );
        selectField.appendChild(opt);

        data.forEach( ( term ) => {
            const opt = document.createElement('option');
            opt.value = term.id;
            opt.innerHTML = term.name;
            if ( term.id == selectedValueId ) {
                opt.setAttribute( 'selected', 'selected' );
            }
            selectField.appendChild(opt);
            hiddenValueField.value = selectedValueId;
        } )
        hiddenValueField.value = selectedValueId;
        categories = categories.concat( data );

        for (let i = 2; i <= totalPages ; i++){
            fetch( url + '&page=' + i)
            .then( response => response.json() )
            .then( data => {
                data.forEach( ( term ) => {
                    const opt = document.createElement('option');
                    opt.value = term.id;
                    opt.innerHTML = term.name;
                    if ( term.id == selectedValueId ) {
                        opt.setAttribute( 'selected', 'selected' );
                    }
                    selectField.appendChild(opt);
                } )
                hiddenValueField.value = selectedValueId;
            });
        }

    });

}



const JeoPartnersPreviewButton = class JeoPartnersPreviewButton extends Component {
    constructor() {
        super();

        let btnDisabled = true;
        window.JeoPartnersPreviewButtonObj = this;
        this.siteURLInput = document.querySelector( 'input[name="_partners_sites_site_url"]' );
        if ( this.siteURLInput && this.siteURLInput.value && this.siteURLInput.value != '' ) {
            btnDisabled = false;
        }
        this.state = {
            isOpen: false,
            btnDisabled,
            btnText: __( 'Preview Import and Save', 'jeo-mps' ),
            html: '',
            httpResponse: false,
            responseData: false,
            modalTitle: __('Preview Import', 'jeo-mps' )
        };
        this.siteURLInput.addEventListener( 'change', () => { window.JeoPartnersPreviewButtonObj.changeURL() } );
    }
    changeURL() {
        window.JeoPartnersPreviewButtonObj.setState( { btnDisabled: false } );
    }
    getThumbnail( item ) {
        if( typeof item._embedded['wp:featuredmedia'][0].source_url === 'undefined' ) {
            if ( typeof item.yoast_head_json === 'undefined' ) {
                return '';
            }
            if ( typeof item.yoast_head_json.og_image === 'undefined' ) {
                return '';
            }
            if ( typeof item.yoast_head_json.og_image[0] === 'undefined' ) {
                return '';
            }
            if ( typeof item.yoast_head_json.og_image[0].url === 'undefined' ) {
                return '';
            }
            return item.yoast_head_json.og_image[0].url;
        }
        return item._embedded['wp:featuredmedia'][0].source_url;
    }
    loadTest() {
        const selectField = document.getElementById('_partners_sites_remote_category' );
        const dateField = document.getElementById('_partners_sites_date' );
        const langField = document.getElementById('_partners_sites_remote_lang' );
        const langValue = langField ? langField.value : 'none';

        this.setState( { btnText: __( 'Loading..', 'jeo-mps' ), btnDisabled: true } );
        let URL = this.siteURLInput.value;
        if ( ! isValidHttpURL( URL ) ) {
            this.setState( { btnDisabled: false, httpResponse: __( 'This Site URL is not valid. Check it and try again.', 'jeo-mps' ), isOpen: true, btnText: __( 'Preview Import and Save', 'jeo-mps' ) } );
            return;
        }
        if ( URL.substr(URL.length - 1) == '/' ) {
            URL = URL.slice(0, -1);
        }
        URL = URL + '/wp-json/wp/v2/posts/?per_page=10&_embed';
        if ( selectField.value && selectField.value != '' ) {
            URL = URL + '&categories[]=' + selectField.value;
        }
        if ( dateField.value && dateField.value != '' ) {
            const format = JSON.parse( dateField.dataset.datepicker );
            const date = new Date( dateField.value );


            URL = URL + '&after=' + date.toISOString();
        }
        if ( langValue != 'none' ) {
            URL = URL + '&lang=' + langValue;
        }

        window.fetch( URL )
        .then( (response) => {
            if( ! response.ok) {
                this.setState( { btnDisabled: false, httpResponse: __( 'The request for that partner is not ok', 'jeo-mps' ), isOpen: true, btnText: __( 'Preview Import and Save', 'jeo-mps' ), modalTitle: __('Preview Import', 'jeo-mps' ) } );
                return;
            }
            let modalTitle = __( 'Preview Import - Showing 10 of ' ) + response.headers.get('x-wp-total');
            if ( parseInt( response.headers.get('x-wp-total') ) <= 10 ) {
                modalTitle = __( 'Preview Import - Posts found: ', 'jeo-mps' ) + response.headers.get('x-wp-total');
            }

            this.setState( { modalTitle } );
            return response.json();
          })
        .then( ( data ) =>{
            if ( data.length > 0 ) {
                this.setState( { btnDisabled: false, httpResponse: false, isOpen: true, responseData: data, btnText: __( 'Preview Import and Save', 'jeo-mps' ) } );
            } else {
                this.setState( { btnDisabled: false, httpResponse: __( 'The request to the partner site with these settings returned 0 posts.', 'jeo-mps' ), isOpen: true, btnText: __( 'Preview Import and Save', 'jeo-mps' ), responseData: false } );
            }
        } )
        .catch( (error) => {
            this.setState( { btnDisabled: false, httpResponse: __( 'The request for that partner is not ok: ' ) + error.message, isOpen: true, btnText: __( 'Preview Import and Save', 'jeo-mps' ) } );
        });

    }
    save() {

        document.getElementById( 'run_import_now' ).value = 'false';
        document.getElementById( 'post' ).submit();
    }
    runNow(){
        document.getElementById( 'run_import_now' ).value = 'true';
        document.getElementById( 'post' ).submit();
    }
    render() {
        const isOpen = this.state.isOpen;
        const btnDisabled = this.state.btnDisabled;
        const httpResponse = this.state.httpResponse;
        const responseData = this.state.responseData;

        return (
	<Fragment>
		{ btnDisabled && (
		<Button variant="primary" disabled>
			{ this.state.btnText }
		</Button>
                ) }
		{ btnDisabled == false && (
		<Button variant="primary" disable onClick={ () => this.loadTest()  }>
			{ this.state.btnText }
		</Button>
                ) }

		{ isOpen && (
		<Modal
			title={ this.state.modalTitle }
			onRequestClose={ () => this.setState( { isOpen: false } ) }
                    >
			{ httpResponse && (
			<div dangerouslySetInnerHTML={ { __html: httpResponse } } />
            ) }
			{ responseData && (
			<div className="preview-posts">
				<div className="preview-save">
					<Button variant="primary" disable onClick={ () => this.runNow() }>
						{ __( 'Save and run now', 'jeo-mps' ) }
					</Button>
				</div>
				<div className="preview-posts__posts">
					<div className="preview-posts__header">
						<div>{ __( 'Featured image', 'jeo-mps' ) }</div>
						<div style={ { maxWidth: '60%' } }>{ __( 'Title', 'jeo-mps' ) }</div>
						<div>{ __( 'Author', 'jeo-mps' ) }</div>
					</div>
					{ responseData.map(item => (
						<article className="preview-posts__post" key={ item.id }>
							<div>
								{ this.getThumbnail( item ) !== '' && (
									<img height="90" width="160" src={ this.getThumbnail( item ) } alt={ item.title.rendered } />
                                ) }
							</div>
							<div><a href={ item.link } target="_blank" rel="noreferrer" dangerouslySetInnerHTML={ { __html: item.title.rendered } } /></div>
							<div>{ item._embedded.author.map(author => author.name).join(', ') }</div>
						</article>
                    )) }
				</div>
				<div className="preview-save">
					<Button variant="primary" disable onClick={ () => this.runNow() }>
						{ __( 'Save and run now', 'jeo-mps' ) }
					</Button>
				</div>
			</div>
            ) }
			{ ! responseData && (
			<div className="preview-save">
				<br></br>
				<Button variant="primary" disable onClick={ () => this.save() }>
					{ __( 'Save anyway', 'jeo-mps' ) }
				</Button>
			</div>
            ) }

		</Modal>
    ) }
	</Fragment>
        );
    }
};

// remove unused wordpress ui things
const css = '#edit-slug-box, #minor-publishing-actions, #misc-publishing-actions { display:none }',
head = document.head || document.getElementsByTagName('head')[0],
style = document.createElement('style');
head.appendChild(style);

style.type = 'text/css';
if (style.styleSheet){
  // This is required for IE8 and below.
  style.styleSheet.cssText = css;
} else {
  style.appendChild(document.createTextNode(css));
}

// init react
document.addEventListener( 'DOMContentLoaded', () => {
    document.getElementById( 'run_import_now' ).value = 'auto_save';

    const siteURLInput = document.querySelector( 'input[name="_partners_sites_site_url"]' );
    document.getElementById( 'major-publishing-actions' ).style.display = 'flex';
    const metabox = document.querySelector( '#publishing-action' );

    render(<JeoPartnersPreviewButton />, metabox )

    // Load categories to categories select field
    fetchCategories();

    siteURLInput.addEventListener( 'change', () => {
        fetchCategories();
    })
    document.getElementById( '_partners_sites_remote_category_value' ).value = document.getElementById('_partners_sites_remote_category' ).value;

    document.getElementById('_partners_sites_remote_category' ).addEventListener( 'change', () => {
        document.getElementById( '_partners_sites_remote_category_value' ).value = document.getElementById('_partners_sites_remote_category' ).value;
    })
    document.getElementById('_partners_sites_remote_lang' ).addEventListener( 'change', () => {
        fetchCategories();
    })
});
