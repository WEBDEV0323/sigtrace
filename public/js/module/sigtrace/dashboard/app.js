var app = new Vue({
    el: '#dLoading',
    data: {
        loading:true,
        data:[],
        filter:'all'
    },
    computed: {
        loadData: function(){
            this.loading = true;
            $("#body-data").removeClass("none").addClass("block");
            this.$http.post('/dashboard/load_dashboard/'+trackerId+'/'+formId, {'filter':this.filter})
            .then(function (response) {
               this.loading = false;
               this.data = response.data; 
            });
        }
    },
    methods: {},
    created: function() {
        this.loadData;
    }
});

