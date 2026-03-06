import { Component, Fragment, render } from '@wordpress/element';
import { Modal, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function buildUrl ( siteURL, path, params = {} ) {
	const url = new URL( path, siteURL );
	for ( const [ key, value ] of Object.entries( params ) ) {
		url.searchParams.set( key, String( value ) );
	}
	return url;
}

function fetchPostTypes ( baseURL, lang = 'none' ) {
	const params = { per_page: 100 };
	if ( lang !== 'none' ) {
		params.lang = lang;
	}

	const url = buildUrl( baseURL, '/wp-json/wp/v2/types/', params );

	return fetch( url ).then( ( response ) => {
		if ( ! response.ok ) {
			return {};
		}
		return response.json();
	} );
}

function fetchTaxonomies ( baseURL, lang = 'none' ) {
	const params = { per_page: 100 };
	if ( lang !== 'none' ) {
		params.lang = lang;
	}

	const url = buildUrl( baseURL, '/wp-json/wp/v2/taxonomies/', params );

	return fetch( url ).then( ( response ) => {
		if ( ! response.ok ) {
			return {};
		}
		return response.json();
	} );
}

function fetchTermsPage ( baseURL, segmentURL, lang, page ) {
	const params = { page, per_page: 100 };
	if ( lang !== 'none' ) {
		params.lang = lang;
	}

	const url = buildUrl( baseURL, segmentURL, params );

	return fetch( url ).then( ( response ) => {
		if ( ! response.ok ) {
			return [];
		}

		return response.json()
	} );
}

function fetchTerms ( baseURL, taxonomy, lang = 'none' ) {
	const segmentURL = `/wp-json/${taxonomy.rest_namespace}/${taxonomy.rest_base}/`;

	const params = { per_page: 100 };
	if ( lang !== 'none' ) {
		params.lang = lang;
	}

	const url = buildUrl( baseURL, segmentURL, params );

	return fetch( url ).then( ( response ) => {
		if ( ! response.ok ) {
			return {};
		}

		const totalPages = response.headers.get('X-WP-TotalPages');

		return Promise.all( [
			response.json(),
			...( Array.from( { length: totalPages - 1 } ).map( ( v, i ) => fetchTermsPage( baseURL, segmentURL, lang, i + 2 ) ) )
		] ).then( ( result ) => result.flat() );
	} );
}

function fillSelectField ( id, options, selected, defaultLabel = null ) {
	const selectField = document.getElementById( id );
	selectField.innerHTML = '';

	if ( defaultLabel ) {
		const defaultOption = document.createElement('option');
		defaultOption.value = '';
		defaultOption.innerHTML = defaultLabel;
		if ( ! selected ) {
			defaultOption.selected = true;
		}
		selectField.appendChild(defaultOption);
	}

	for ( const [ value, label ] of Object.entries( options ) ) {
		const option = document.createElement('option');
		option.value = value;
		option.innerHTML = label;
		if ( selected == value ) {
			option.selected = true;
		}
		selectField.appendChild(option);
	}
}

function fillPostTypeField ( id, postTypes, selected ) {
	const options = {};

	for ( const postType of Object.values( postTypes ) ) {
		options[ postType.slug ] = postType.name;
	}

	fillSelectField( id, options, selected );
}

function fillTaxonomyField ( id, postType, taxonomies, selected ) {
	const options = {};

	const filteredTaxonomies = Object.values( taxonomies ).filter( ( taxonomy ) => {
		return taxonomy.types.includes( postType );
	} );

	for ( const taxonomy of filteredTaxonomies ) {
		options[ taxonomy.slug ] = taxonomy.name;
	}

	fillSelectField( id, options, selected, __( 'All taxonomies', 'jeo-mps' ) );
}

function fillTermField ( id, terms, selected ) {
	const options = {};

	for ( const term of terms ) {
		options[ term.id ] = term.name;
	}

	fillSelectField( id, options, selected, __( 'All terms', 'jeo-mps' ) );
}

function isValidHttpURL ( siteURL ) {
    try {
        const testURL = new URL(siteURL);
		return testURL.protocol === "http:" || testURL.protocol === "https:";
    } catch (err) {
        return false;
    }
}
class JeoMPSContext {
	constructor() {
		this.selected = globalThis.jeo_partners_posts_data;

		this.local = {
			postTypes: null,
			taxonomies: null,
			terms: {},
		};

		this.remote = {
			postTypes: null,
			taxonomies: null,
			terms: {},
		};
	}

	init () {
		const localPromise = this.initLocal();

		const remotePromise = this.selected.remote.siteURL ? this.initRemote() : Promise.resolve();

		return Promise.all([ localPromise, remotePromise ]);
	}

	initLocal () {
		return Promise.all( [ this.fetchLocalPostTypes(), this.fetchLocalTaxonomies() ] ).then( () => {
			this.setLocalPostType( this.selected.local.postType );
			this.setLocalTaxonomy( this.selected.local.taxonomy, true );

			this.fetchLocalTerms( this.local.taxonomies[ this.selected.local.taxonomy ] ).then( () => {
				this.setLocalTerm( this.selected.local.term );
			} );

			fillPostTypeField( '_partners_sites_local_post_type', this.local.postTypes, this.selected.local.postType );
		} );
	}

	initRemote () {
		return Promise.all( [ this.fetchRemotePostTypes(), this.fetchRemoteTaxonomies() ] ).then( () => {
			this.setRemotePostType( this.selected.remote.postType );
			this.setRemoteTaxonomy( this.selected.remote.taxonomy, true );

			this.fetchRemoteTerms( this.remote.taxonomies[ this.selected.remote.taxonomy ] ).then( () => {
				this.setRemoteTerm( this.selected.remote.term );
			} );

			fillPostTypeField( '_partners_sites_remote_post_type', this.remote.postTypes, this.selected.remote.postType );
		} );
	}

	fetchLocalPostTypes () {
		return fetchPostTypes( this.selected.local.siteURL ).then( ( postTypes ) => {
			this.local.postTypes = postTypes;
		} );
	}

	fetchLocalTaxonomies () {
		return fetchTaxonomies( this.selected.local.siteURL ).then( ( taxonomies ) => {
			this.local.taxonomies = taxonomies;
		} );
	}

	fetchLocalTerms ( taxonomy ) {
		return fetchTerms( this.selected.local.siteURL, taxonomy ).then( ( terms ) => {
			this.local.terms[ taxonomy.slug ] = terms;
		} );
	}

	fetchRemotePostTypes () {
		return fetchPostTypes( this.selected.remote.siteURL, this.selected.remote.lang ).then( ( postTypes ) => {
			this.remote.postTypes = postTypes;
		} );
	}

	fetchRemoteTaxonomies () {
		return fetchTaxonomies( this.selected.remote.siteURL, this.selected.remote.lang ).then( ( taxonomies ) => {
			this.remote.taxonomies = taxonomies;
		} );
	}

	fetchRemoteTerms ( taxonomy) {
		return fetchTerms( this.selected.remote.siteURL, taxonomy, this.selected.remote.lang ).then( ( terms ) => {
			this.remote.terms[ taxonomy.slug ] = terms;
		} );
	}

	getRemotePostURL () {
		const postType = this.remote.postTypes[ this.selected.remote.postType ];

		if ( postType ) {
			return `/wp-json/${postType.rest_namespace}/${postType.rest_base}`;
		}

		return `/wp-json/wp/v2/${this.selected.remote.postType}`;
	}

	setLocalPostType (postType) {
		this.selected.local.postType = postType;
		fillTaxonomyField( '_partners_sites_local_taxonomy', postType, this.local.taxonomies, this.selected.local.taxonomy );
	}

	setLocalTaxonomy (taxonomy) {
		this.selected.local.taxonomy = taxonomy;
		if ( this.local.terms[ taxonomy ] ) {
			fillTermField( '_partners_sites_local_category', this.local.terms[ taxonomy ], this.selected.local.term );
		} else {
			this.fetchLocalTerms( this.local.taxonomies[ taxonomy ] ).then( () => {
				fillTermField( '_partners_sites_local_category', this.local.terms[ taxonomy ], this.selected.local.term );
			} );
		}
	}

	setLocalTerm (term) {
		this.selected.local.term = term;
	}

	setRemotePostType (postType) {
		this.selected.remote.postType = postType;
		fillTaxonomyField( '_partners_sites_remote_taxonomy', postType, this.remote.taxonomies, this.selected.remote.taxonomy );
	}

	setRemoteTaxonomy (taxonomy) {
		this.selected.remote.taxonomy = taxonomy;
		if ( this.remote.terms[ taxonomy ] ) {
			fillTermField( '_partners_sites_remote_category', this.remote.terms[ taxonomy ], this.selected.remote.term );
		} else {
			this.fetchRemoteTerms( this.remote.taxonomies[ taxonomy ] ).then( () => {
				fillTermField( '_partners_sites_remote_category', this.remote.terms[ taxonomy ], this.selected.remote.term );
			} );
		}
	}

	setRemoteTerm (term) {
		this.selected.remote.term = term;
	}

	setRemoteLang (lang) {
		this.selected.remote.siteURL = lang;
		if ( this.selected.remote.siteURL ) {
			this.initRemote();
		}
	}

	setRemoteURL (siteURL) {
		this.selected.remote.siteURL = siteURL;
		if ( siteURL ) {
			this.initRemote();
		}
	}
}

class JeoPartnersPreviewButton extends Component {
    constructor() {
        super();

        let btnDisabled = true;
        globalThis.JeoPartnersPreviewButtonObj = this;
        this.siteURLInput = document.getElementById( '_partners_sites_site_url' );
        if ( this.siteURLInput && this.siteURLInput.value ) {
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
        this.siteURLInput.addEventListener( 'change', () => { globalThis.JeoPartnersPreviewButtonObj.changeURL() } );
    }
    changeURL() {
        globalThis.JeoPartnersPreviewButtonObj.setState( { btnDisabled: false } );
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
        const taxonomyField = document.getElementById('_partners_sites_remote_taxonomy' );
        const selectField = document.getElementById('_partners_sites_remote_category' );
        const dateField = document.getElementById('_partners_sites_date' );
        const langField = document.getElementById('_partners_sites_remote_lang' );
        const langValue = langField ? langField.value : 'none';

        this.setState( { btnText: __( 'Loading...', 'jeo-mps' ), btnDisabled: true } );

        if ( ! isValidHttpURL( this.siteURLInput.value ) ) {
            this.setState( { btnDisabled: false, httpResponse: __( 'This Site URL is not valid. Check it and try again.', 'jeo-mps' ), isOpen: true, btnText: __( 'Preview Import and Save', 'jeo-mps' ) } );
            return;
        }

		const params = { per_page: 10, _embed: '' };

		let taxonomySlug = taxonomyField.value;
		if ( taxonomySlug === 'category' ) {
			taxonomySlug = 'categories';
		} else if ( taxonomySlug === 'post_tag' ) {
			taxonomySlug = 'tags';
		}

        if ( selectField.value ) {
            params[taxonomySlug] = selectField.value;
        }

        if ( dateField.value ) {
            const format = JSON.parse( dateField.dataset.datepicker );
            const date = new Date( dateField.value );
            params.after = date.toISOString();
        }

        if ( langValue !== 'none' ) {
            params.lang = langValue;
        }

		const url = buildUrl( this.siteURLInput.value, jeompsContext.getRemotePostURL(), params );

        globalThis.fetch( url )
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
        .then( ( data ) => {
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
}

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

    document.getElementById( 'major-publishing-actions' ).style.display = 'flex';
    const metabox = document.getElementById( 'publishing-action' );

    render(<JeoPartnersPreviewButton />, metabox );

	globalThis.jeompsContext = new JeoMPSContext();

	globalThis.jeompsContext.init().then( () => {
		document.getElementById( '_partners_sites_site_url' ).addEventListener( 'change', ( event ) => {
			jeompsContext.setRemoteURL( event.target.value );
		})

		document.getElementById('_partners_sites_local_post_type' ).addEventListener( 'change', ( event ) => {
			jeompsContext.setLocalPostType( event.target.value );
		});

		document.getElementById('_partners_sites_local_taxonomy' ).addEventListener( 'change', ( event ) => {
			jeompsContext.setLocalTaxonomy( event.target.value );
		});

		document.getElementById('_partners_sites_local_category' ).addEventListener( 'change', ( event ) => {
			jeompsContext.setLocalTerm( event.target.value );
		});

		document.getElementById('_partners_sites_remote_post_type' ).addEventListener( 'change', ( event ) => {
			jeompsContext.setRemotePostType( event.target.value );
		});

		document.getElementById('_partners_sites_remote_taxonomy' ).addEventListener( 'change', ( event ) => {
			jeompsContext.setRemoteTaxonomy( event.target.value );
		});

		document.getElementById( '_partners_sites_remote_category_value' ).value = document.getElementById('_partners_sites_remote_category' ).value;
		document.getElementById('_partners_sites_remote_category' ).addEventListener( 'change', ( event ) => {
			document.getElementById( '_partners_sites_remote_category_value' ).value = event.target.value;
			jeompsContext.setRemoteTerm( event.target.value );
		});

		if ( document.getElementById('_partners_sites_remote_lang' ) ) {
			document.getElementById('_partners_sites_remote_lang' ).addEventListener( 'change', ( event ) => {
				jeompsContext.setRemoteLang( event.target.value );
			});
		}
	} );

});
