$(function(){

//// ED Utility
    var EDToolBox = {
        Spinner: {
            append: function($el) {
                $el.append('<div class="spinner"><div class="rect1"></div><div class="rect2"></div><div class="rect3"></div><div class="rect4"></div><div class="rect5"></div></div>');
            },
            remove: function($el) {
                $el.find('.spinner').remove();
            }
        }
    }


//// ED MODEL
    var ED = Backbone.Model.extend({
        initialize: function(){
            var selectable = new Backbone.Picky.Selectable(this);
            _.extend(this, selectable);

            this.listenTo(this, 'selected', this.activate);
            this.listenTo(this, 'deselected', this.desactivate);
        },
        defaults: function() {
            return {
                id: 0,
                authors: '',
                title: '',
                desc: '',
                content: '',
                updated_at: '',
                created_at: '',
                active: false,
                editing: false,
                encrypt: true,
                updating: false,
                error: '',
                success: '',
                forceUpdatePassphrase: false,
                hide: false
            };
        },
        activate: function() {
            this.set({ active: true, error: '', success: '' });
        },
        desactivate: function() {
            this.set({ active: false, error: '', success: '' });
        },
        edit: function() {
            this.set({ editing: true, error: '', success: '' });
        },
        cancelEdition: function() {
            this.set({ editing: false, error: '', success: '' });
        },
        decrypt: function(passphrase) {
            $.ajax({
                url: '/documents/' + this.id + '/decrypt',
                dataType: 'json',
                type: 'POST',
                data: { 'passphrase' : passphrase },
                beforeSend: function() {
                    this.set({ updating: true });
                }.bind(this),
                success: function(data) {
                    this.set({
                        'content': data.content,
                        'encrypt': false,
                        'updating': false,
                        'error': '',
                        'success': data.message
                    });
                }.bind(this),
                error: function(xhr, status, err) {
                    this.set({
                        'error': xhr.responseJSON.message,
                        'updating': false,
                        'success': ''
                    });
                }.bind(this)
            });
        },
        encrypt: function(passphrase, properties) {
            var bForceNewPassphrase = this.get('forceUpdatePassphrase');

            $.ajax({
                url: '/documents/' + this.id + '/encrypt',
                dataType: 'json',
                type: 'POST',
                data: {
                    'passphrase' : passphrase,
                    'title'      : properties.title,
                    'desc'       : properties.desc,
                    'content'    : properties.content,
                    'authors'    : properties.authors,
                    'forceNewPassphrase' : bForceNewPassphrase
                },
                beforeSend: function() {
                    this.set({
                        title: properties.title,
                        desc: properties.desc,
                        content: properties.content,
                        authors: properties.authors,
                        updating: true
                    });
                }.bind(this),
                success: function(response) {
                    this.set({
                        'id': response.data.id,
                        'content': response.data.content,
                        'desc': response.data.desc,
                        'title': response.data.title,
                        'authors': response.data.authors,
                        'created_at': response.data.created_at,
                        'updated_at': response.data.updated_at,
                        'editing': false,
                        'success': response.message,
                        'error': '',
                        'forceUpdatePassphrase': false
                    });
                }.bind(this),
                error: function(xhr, status, err) {
                    this.set({
                        'error': xhr.responseJSON.message,
                        'updating': false,
                        'editing': true,
                        'success': '',
                        'forceUpdatePassphrase': true
                    });
                }.bind(this)
            });
        },
        create: function() {
            this.set({ editing: true, encrypt: false });
        },
        delete: function() {
            this.destroy();
        }
    });


//// ED COLLECTION
    var EDList = Backbone.Collection.extend({
        url: '/documents',
        model: ED,
        initialize: function() {
            var singleSelect = new Backbone.Picky.SingleSelect(this);
            _.extend(this, singleSelect);
        },
        filterByAuthors: function(authorsName) {
            var _this = this;

            if (_.isEmpty(authorsName) ) {
                _.each(_this.models, function(mod, idx, list) {
                    _this.at(idx).set('hide', false);
                });
            }
            else {
                authorsName = _this.normalizeArrayValues(authorsName );

                _.each(_this.models, function(mod, idx, list) {
                    var aAuthors = _this.normalizeArrayValues(mod.get('authors') );
                    var hasAllAuthors = _.difference(authorsName, aAuthors);

                    if (_.isEmpty(hasAllAuthors) ) {
                        _this.at(idx).set('hide', false);
                    }
                    else {
                        _this.at(idx).set('hide', true);
                    }
                });

            }
        },
        getAllAuthors: function() {
            var json = [];
            var knownAuthors = {};

            _.each(this.models, function(mod){
                var aAuthors = mod.get('authors');
                _.each(aAuthors, function(name){
                    name = name.toUpperCase();

                    if (_.has(knownAuthors, name) ) {
                        knownAuthors[name] = knownAuthors[name] + 1;
                    }
                    else {
                        knownAuthors[name ] = 1;
                    }
                });
            });

            _.each(knownAuthors, function(val, key) {
                json.push({
                    name: key.toLowerCase(),
                    count: val
                });
            });

            return _.sortBy(json, 'name');
            //[{ 'name': 'Nicolas', count: 3 },{ 'name': 'Camille', count: 2 }];
        },
        comparator: function(ed) {
            var str = ed.get('title').toUpperCase()
            return this.sansAccent(str);
        },
        // @see http://www.finalclap.com/faq/257-javascript-supprimer-remplacer-accent
        sansAccent: function(str){
            var accent = [
                /[\300-\306]/g, /[\340-\346]/g, // A, a
                /[\310-\313]/g, /[\350-\353]/g, // E, e
                /[\314-\317]/g, /[\354-\357]/g, // I, i
                /[\322-\330]/g, /[\362-\370]/g, // O, o
                /[\331-\334]/g, /[\371-\374]/g, // U, u
                /[\321]/g, /[\361]/g, // N, n
                /[\307]/g, /[\347]/g, // C, c
            ];
            var noaccent = ['A','a','E','e','I','i','O','o','U','u','N','n','C','c'];

            //var str = this;
            for(var i = 0; i < accent.length; i++){
                str = str.replace(accent[i], noaccent[i]);
            }

            return str;
        },
        normalizeArrayValues: function(arr) {
            var _this = this;
            return _.map(arr, function(val) {
                return _this.sansAccent(val.toUpperCase() );
            });
        }
    });


    var EDs = new EDList;


//// ED VIEW
    var EDDetailView = Backbone.View.extend({
        tagName:  "div",
        attributes: {
            'class': 'email-content'
        },
        templateShow: _.template($('#ed-detail').html()),
        templateEdit: _.template($('#ed-edit').html()),
        events: {
            "submit #ed-form-decrypt" : "decrypt",
            "click #ed-button-edit"   : "edit",
            "click #ed-button-cancel" : "cancel",
            "click #ed-button-delete" : "delete",
            "submit #ed-form-save"    : "save"
        },
        initialize: function() {
            this.listenTo(this.model, 'change', this.render);
            this.listenTo(this.model, 'change:updating', this.renderUpdating);
            this.listenTo(this.model, 'destroy', this.clear);
        },
        render: function() {
            // Do render view only if necessary
            var alterAttributes = _.keys(_.omit(this.model.changedAttributes(), ['updating'])).length;
            if (alterAttributes == 0) {
                return this;
            }

            if (this.model.get('editing') ) {
                this.$el.html(this.templateEdit(this.model.toJSON() ) );
                this.editor = CKEDITOR.replace('ed-edit-content', {
                    'language': 'fr',
                    toolbarGroups: [
                        { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
                        { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
                        { name: 'styles', groups: [ 'styles' ] },
                        { name: 'insert', groups: [ 'insert' ] },
                        { name: 'links', groups: [ 'links' ] },
                        { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
                        { name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
                        { name: 'forms', groups: [ 'forms' ] },
                        { name: 'tools', groups: [ 'tools' ] },
                        { name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
                        { name: 'others', groups: [ 'others' ] },
                        { name: 'colors', groups: [ 'colors' ] },
                        { name: 'about', groups: [ 'about' ] }
                    ],
                    removeButtons: 'Underline,Subscript,Superscript,Cut,Copy,Scayt,Anchor,Image,SpecialChar,Maximize,Blockquote,Styles,About'
                });
            }
            else {
                this.$el.html(this.templateShow(this.model.toJSON() ) );
            }
            return this;
        },
        renderUpdating: function() {
            if (this.model.get('updating') ) {
                EDToolBox.Spinner.append(this.$('.email-content-header form:first'));
            }
            else {
                EDToolBox.Spinner.remove(this.$('.email-content-header form:first'));
            }
        },
        clear: function() {
            this.remove();
        },
        decrypt: function(e) {
            e.preventDefault();
            var $password = this.$('#ed-form-decrypt input');
            var passphrase = $password.get(0).value.trim();

            if (!passphrase) {
                $password.addClass('form-field-error');
                return;
            }
            else { $password.removeClass('form-field-error'); }

            this.model.decrypt(passphrase);
        },
        edit: function() {
            this.model.edit();
        },
        cancel: function() {
            this.model.cancelEdition();
        },
        save: function(e) {
            e.preventDefault();

            var $password = this.$('#ed-input-password');
            var $passwordConfirm = this.$('#ed-input-password-confirm');
            var $title = this.$('#ed-edit-title');
            var $desc = this.$('#ed-edit-desc');
            var $authors = this.$('#ed-edit-authors');

            var passphrase = $password.val().trim();
            if (this.model.get('forceUpdatePassphrase') ) {
                var passphrase2 = $passwordConfirm.val().trim();
                if (_.isEmpty(passphrase) || passphrase != passphrase2) {
                    $password.addClass('form-field-error');
                    $passwordConfirm.addClass('form-field-error');
                    window.alert("Pour définir une nouvelle passphrase, veuillez la renseigner deux fois");
                    return;
                }
            }

            var title = $title.val().trim();
            var desc = $desc.val().trim();
            var authors = $authors.val().trim();

            this.editor.updateElement();
            var $content = this.$('#ed-edit-content');
            var content = $content.val().trim();

            if (!passphrase) { $password.addClass('form-field-error'); }
            else { $password.removeClass('form-field-error'); }

            if (!title) { $title.addClass('form-field-error'); }
            else { $title.removeClass('form-field-error'); }

            if (!authors) { $authors.addClass('form-field-error'); }
            else { $authors.removeClass('form-field-error'); }

            if (!passphrase || !title || !authors) {
              return;
            }

            this.model.encrypt(passphrase, {
                'title'   : title,
                'desc'    : desc,
                'authors' : authors,
                'content' : content
            });
        },
        delete: function() {
            if (window.confirm("Voulez-vous vraiment supprimer ces données définitivement ?")) {
                this.model.delete();
            }
        }
    });


    var EDItemView = Backbone.View.extend({
        tagName:  "div",
        template: _.template($('#ed-list-item').html()),
        events: {
            "click" : "display"
        },
        initialize: function() {
            this.listenTo(this.model, 'change', this.render);
            this.listenTo(this.model, 'destroy', this.clear);
        },
        render: function() {
            this.$el.html(this.template(this.model.toJSON() ) );
            return this;
        },
        display: function() {
            this.model.collection.select(this.model);
        },
        clear: function() {
            this.remove();
        }
    });


    var EDToolbarView = Backbone.View.extend({
        tagName:  "div",
        template: _.template($('#ed-toolbar').html() ),
        events: {
            "click #ed-new-trigger" : "new",
            "click #authorsFilter .pure-menu-link" : "authorFilter"
        },
        initialize: function() {
        },
        render: function() {
            var authors = EDs.getAllAuthors();
            this.$el.html(this.template({ authors: authors }));
            return this;
        },
        new: function() {
            Backbone.trigger('documents:newToCreate');
        },
        authorFilter: function(e) {
            var $e = $(e.target);
            var authorName = $e.data('author');
            var $container = $('#authorsFilter');
            var sActiveFilters = $container.data('filter');
            var aActiveFilters = ( _.isString(sActiveFilters) && sActiveFilters != '' ? sActiveFilters.split(',') : [] );

            if (_.isUndefined(authorName) || _.isEmpty(authorName) ) {
                return false;
            }

            if ($e.hasClass('active') ) {
                aActiveFilters = _.without(aActiveFilters, authorName);
                $e.removeClass('active');
            } else {
                aActiveFilters.push(authorName);
                aActiveFilters = _.uniq(aActiveFilters);
                $e.addClass('active');
            }
            sActiveFilters = ( _.isEmpty(aActiveFilters) ? '' : aActiveFilters.join(',') );
            $container.data('filter', sActiveFilters );

            Backbone.trigger('documents:filter', aActiveFilters);
        }
    });


//// ED APPLICATION

    var AppView = Backbone.View.extend({
        el: $("#layout"),
        initialize: function() {
            this.edList = this.$('#list');
            this.edDetail = this.$('#main');
            this.edToolbar = this.$('#nav');

            this.listenTo(EDs, 'add', this.addOne);
            this.listenTo(EDs, 'reset', this.addAll);
            //this.listenTo(EDs, 'all', this.render);
            this.listenTo(EDs, 'select:one', this.displayDetail);
            this.listenTo(EDs, 'sort', this.sort);

            this.listenTo(Backbone, 'documents:newToCreate', this.createOneDocument);
            this.listenTo(Backbone, 'documents:filter', this.filter);

            EDs.fetch();
            EDs.sort();
        },
        renderToolbar: function() {
            var toolbar = new EDToolbarView();
            this.edToolbar.html(toolbar.render().el );
        },
        displayDetail: function(ed) {
            var detailView = new EDDetailView({model: ed});
            this.edDetail.html(detailView.render().el);
        },
        addOne: function(ed) {
            var itemView = new EDItemView({model: ed});
            this.edList.append(itemView.render().el);
        },
        addAll: function() {
            EDs.each(this.addOne, this);
            this.renderToolbar();
        },
        createOneDocument: function() {
            var oDocument = new ED();
            EDs.add(oDocument);
            this.displayDetail(oDocument);
            oDocument.create();
        },
        sort: function() {
            this.edList.empty();
            this.addAll();
        },
        filter: function(authorsName) {
            EDs.filterByAuthors(authorsName);
        }
    });

    var App = new AppView;

});
