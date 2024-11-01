/**
 * Created with JetBrains PhpStorm.
 * User: ben
 * Date: 1/6/15
 * Time: 10:40 PM
 * To change this template use File | Settings | File Templates.
 */

var DSCF_DTG_Admin = {

    pendingCharts:Array(),
    chartsToDo:Array(),
    isGoogleLoaded:false,
    isGoogleVisLoaded:false,

    loadGoogle:function(){
        if(DSCF_DTG_Charts.isGoogleLoaded){
            DSCF_DTG_Charts.loadGoogleVisualization();
        }else{
            var script = document.createElement("script");
            script.src = "https://www.google.com/jsapi?callback=DSCF_DTG_Charts.loadGoogleVisualization";
            script.type = "text/javascript";
            document.getElementsByTagName("head")[0].appendChild(script);
        }
    },

    loadGoogleVisualization:function(){
        //Validate google object is loaded
        if(google == undefined){
            DSCF_DTG_Charts.loadGoogle();
            return; //Do the main loader
        }else{
            DSCF_DTG_Charts.isGoogleLoaded = true;
        }

        //Load the visualizer
        if(DSCF_DTG_Charts.isGoogleVisLoaded){
            DSCF_DTG_Charts.drawGoogleCharts();
        }else{
            google.load("visualization", "1", {'packages':['corechart','annotationchart','gauge', 'timeline', 'bar'], "callback" : DSCF_DTG_Charts.drawGoogleCharts});
        }
    },

    /*
     *  This routine is used when you need to call ajax to get the Data
     */
    enqueueChartViaAjax:function(elementId, ajaxUrl, chartTypeId, options){
        var newChart = new dimbalSingleChart();
        newChart.ajaxUrl = ajaxUrl;
        newChart.elementId = elementId;
        newChart.chartTypeId = chartTypeId;
        newChart.options = options;
        newChart.performAjax();
    },

    /*
     * This routine is used when you already have the data to use in the chart
     */
    addChart:function(elementId, chartTypeId, data, options){
        var newChart = new dimbalSingleChart();
        newChart.elementId = elementId;
        newChart.chartTypeId = chartTypeId;
        newChart.data = data;
        newChart.options = options;
        DSCF_DTG_Charts.chartsToDo.push(newChart);
        DSCF_DTG_Charts.drawGoogleCharts();
    },

    drawGoogleCharts:function(){
        //Validate that google and google.visualization is loaded
        if(DSCF_DTG_Charts.isGoogleLoaded){
            if(google.visualization == undefined){
                DSCF_DTG_Charts.loadGoogleVisualization();
                return; //Do the visualization loader
            }else{
                DSCF_DTG_Charts.isGoogleVisLoaded = true;
            }
        }else{
            DSCF_DTG_Charts.loadGoogle();
            return;	//Start over at the google loader and exit this function
        }

        // Loop through any elements added to our to-do array...
        setTimeout(function(){
            // While not ideal -- we saw an issue on prod that would attempt to interact with the google vis before the function was ready despite the undefined checks
            while(DSCF_DTG_Charts.chartsToDo.length > 0){
                var chartObject = DSCF_DTG_Charts.chartsToDo[0];
                if(chartObject != undefined){
                    // Now hand off to the actual chart elements
                    console.log("About to hand off to Draw Chart");
                    chartObject.drawChart();
                }
                DSCF_DTG_Charts.arrayRemove(DSCF_DTG_Charts.chartsToDo, 0);
            }
        }, 500);
    },

    arrayRemove:function(o, from, to){
        var rest = o.slice((to || from) + 1 || o.length);
        o.length = from < 0 ? o.length + from : from;
        return o.push.apply(o, rest);
    }

};

var dimbalSingleChart = function(elementId, chartTypeId, data, options)
{

    console.log("Inside the Create Single Chart Class");

    this.elementId = elementId;
    this.chartTypeId = chartTypeId;
    this.data = data;
    this.options = options;

    this.performAjax = function(){
        console.log("Inside the Perform Ajax function");
        $.ajax({
            chartObject: this,
            dataType: "json",
            url: this.ajaxUrl+"&at="+AJAX_AT+"&ah="+AJAX_AH,
            success: function(responseData) {
                if(responseData.data != undefined){
                    this.chartObject.data = responseData.data;
                }

                DSCF_DTG_Charts.chartsToDo.push(this.chartObject);
                DSCF_DTG_Charts.drawGoogleCharts();
            },
            error: function(jqXHR, textStatus, errorThrown){
                console.log("Error in AJAX request ["+textStatus+"] ["+errorThrown+"]");
                $("#"+this.chartObject.elementId).html("<p style='text-align:center; padding:100px;'>No data available for display</p>");
            }
        });
    };

    this.drawChart = function(){
        console.log("Inside the DrawChart function");
        console.dir(this);
        var data=null;
        var chart=null;

        // Check for bad data
        if(this.data == undefined){
            console.log("No data available to display.");
            $("#"+this.elementId).html("No data available to display.");
            return;
        }

        try{
            switch(this.chartTypeId){
                case 1:
                    // Pie Chart
                    data = google.visualization.arrayToDataTable(this.data);
                    chart = new google.visualization.PieChart(document.getElementById(this.elementId));
                    chart.draw(data, this.options);
                    break;
                case 2:
                    // Column Chart
                    data = google.visualization.arrayToDataTable(this.data);
                    chart = new google.visualization.ColumnChart(document.getElementById(this.elementId));
                    chart.draw(data, this.options);
                    break;
                case 3:
                    // Annotation Chart
                    for(i in this.data){
                        if(i==0){
                            // Skip first entry as it is labeled DATE
                        }else{
                            var dateString = this.data[i][0];
                            //console.log("Date String: ["+dateString+"]");
                            var dateObject = new Date(dateString);
                            this.data[i][0] = dateObject;
                        }
                    }
                    data = google.visualization.arrayToDataTable(this.data);
                    chart = new google.visualization.AnnotationChart(document.getElementById(this.elementId));
                    chart.draw(data, this.options);
                    break;
                case 4:
                    // Table Chart
                    data = google.visualization.arrayToDataTable(this.data);
                    chart = new google.visualization.Table(document.getElementById(this.elementId));
                    chart.draw(data, this.options);
                    break;
                case 5:
                    // Gauge Chart
                    data = google.visualization.arrayToDataTable(this.data);
                    chart = new google.visualization.Gauge(document.getElementById(this.elementId));
                    chart.draw(data, this.options);
                    break;
                case 6:
                    // Scatter Chart
                    data = google.visualization.arrayToDataTable(this.data);
                    chart = new google.visualization.ScatterChart(document.getElementById(this.elementId));
                    chart.draw(data, this.options);
                    break;
                case 7:
                    // Line Chart
                    data = google.visualization.arrayToDataTable(this.data);
                    chart = new google.visualization.LineChart(document.getElementById(this.elementId));
                    chart.draw(data, this.options);
                    break;
                case 8:
                    // Timeline Chaty
                    data = google.visualization.arrayToDataTable(this.data);
                    chart = new google.visualization.Timeline(document.getElementById(this.elementId));
                    chart.draw(data, this.options);
                    break;
                case 9:
                    // Bar Chart
                    data = google.visualization.arrayToDataTable(this.data);
                    chart = new google.visualization.BarChart(document.getElementById(this.elementId));
                    chart.draw(data, this.options);
                    break;
            }
        }catch(err){
            console.log("Error message Caught: "+err.message);
            $("#"+this.elementId).html("No data available to display.");
        }

    };

    this.drawBarChart = function(){

    };
};
