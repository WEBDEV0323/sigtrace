var app = new Vue({
    el: '#reports',
    data: {
        loading:true,
        reports:[],
        data:[],
        footerReports:[],
        filter:'all'
    },
    computed: {
        loadData: function(){
            this.loading = true;
            $("#body-data").removeClass("none").addClass("block");
            this.$http.post('/signalcalendar/load_dashboard/'+trackerId+'/'+formId, {'filter':this.filter})
            .then((response) => {
               this.loading = false;
               this.reports = response.body;
               this.filter = response.body.filter;
               this.data = response.body.data;
               this.footerReports = response.body.footer; 
            });
        }
    },
    methods: {
        showLists: function(trackerId, formId, dashboard_id=1, type="all") {
            window.location="/signalcalendar/list/"+trackerId+"/"+formId+"/"+dashboard_id+"/"+0+"?type="+type+"&filter="+this.filter;
            // window.location="/ri/dashboard/list/"+trackerId+"/"+formId+"/"+dashboard_id+"/"+0+"/"+type+"/"+this.filter;
        }
    },
    created: function() {
        this.loadData;
    }
});

