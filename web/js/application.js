$(function(){

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
                encrypt: true
            };
        },
        activate: function() {
            this.set({ active: true });
        },
        desactivate: function() {
            this.set({ active: false });
        },
        edit: function() {
            this.set({ editing: true });
        },
        cancelEdition: function() {
            this.set({ editing: false });
        },
        decrypt: function(passphrase) {
            $.ajax({
                url: '/documents/' + this.id + '/decrypt',
                dataType: 'json',
                type: 'POST',
                data: { 'passphrase' : passphrase },
                success: function(data) {
                    this.set({ 'content': data.content, encrypt: false });
                }.bind(this),
                error: function(xhr, status, err) {
                    console.error(
                        '/documents/' + this.id + '/decrypt',
                        status,
                        err.toString()
                    );
                }.bind(this)
            });
        },
        encrypt: function(passphrase, properties) {
            $.ajax({
                url: '/documents/' + this.id + '/encrypt',
                dataType: 'json',
                type: 'POST',
                data: {
                    'passphrase' : passphrase,
                    'title'      : properties.title,
                    'desc'       : properties.desc,
                    'content'    : properties.content,
                    'authors'    : properties.authors
                },
                success: function(response) {
                    this.set({
                        'content': response.data.content,
                        'desc': response.data.desc,
                        'title': response.data.title,
                        'authors': response.data.authors,
                        'created_at': response.data.created_at,
                        'updated_at': response.data.updated_at
                    });
                }.bind(this),
                error: function(xhr, status, err) {
                    console.error(
                        '/documents/' + this.id + '/decrypt',
                        status,
                        err.toString()
                    );
                }.bind(this)
            });
        }
    });


//// ED COLLECTION
    var EDList = Backbone.Collection.extend({
        url: '/documents',
        model: ED,
        initialize: function() {
            var singleSelect = new Backbone.Picky.SingleSelect(this);
            _.extend(this, singleSelect);
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
            "submit #ed-form-save"    : "save"
        },
        initialize: function() {
            this.listenTo(this.model, 'change', this.render);
            this.listenTo(this.model, 'destroy', this.clear);
        },
        render: function() {
            if (this.model.get('editing') ) {
                this.$el.html(this.templateEdit(this.model.toJSON() ) );
            }
            else {
                this.$el.html(this.templateShow(this.model.toJSON() ) );
            }
            return this;
        },
        clear: function() {
            this.model.destroy();
        },
        decrypt: function(e) {
            e.preventDefault();
            var passphrase = this.$('#ed-form-decrypt input').get(0).value.trim();
            if (!passphrase) {
              return;
            }
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
            var passphrase = this.$('#ed-form-save input').get(0).value.trim();
            var title = $('#ed-edit-title').val().trim();
            var desc = $('#ed-edit-desc').val().trim();
            var authors = $('#ed-edit-authors').val().trim();
            var content = $('#ed-edit-content').val().trim();
            if (!passphrase || !title) {
              return;
            }
            this.model.encrypt(passphrase, {
                'title'   : title,
                'desc'    : desc,
                'authors' : authors,
                'content' : content
            })
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
            this.model.destroy();
        }
    });


//// ED APPLICATION

    var AppView = Backbone.View.extend({
        el: $("#layout"),
        initialize: function() {
            this.edList = this.$('#list');
            this.edDetail = this.$('#main');

            this.listenTo(EDs, 'add', this.addOne);
            this.listenTo(EDs, 'reset', this.addAll);
            this.listenTo(EDs, 'all', this.render);
            this.listenTo(EDs, 'select:one', this.displayDetail)

            EDs.fetch();
        },
        render: function() {

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
        },
    });

    var App = new AppView;

});