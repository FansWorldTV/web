var complaint = {
    listType: [],
    pageList : 2,
    init: function(){
        complaint.changeType();
        $("#addMore.complaints").on('click', function(){
            return complaint.pager()
        });
    },
    changeType: function(){
        $(".complaints .complaintType li a").on('click', function(){
            $(".cont ul.listComplaints li").remove();
            var self = $(this);
            self.toggleClass('bold');
            if( complaint.listType.indexOf(self.parent().attr('class')) > -1 ){
                complaint.listType = removeArrayElement(complaint.listType, self.parent().attr('class'));                
            }else{
                complaint.listType.push(self.parent().attr('class'));
            }
            
            complaint.pageList = 1;
            complaint.pager();
        });
    },
    pager: function(){
        $("a.titleType").addClass('loading');
        $("#addMore").addClass('loading');
        $("ul.complaintType").addClass('hidden');
        ajax.genericAction('complaint_ajaxlist', {
            'page': complaint.pageList, 
            'type': complaint.listType
        }, function(response){
            if(response){
                for(i in response.complaints){
                    var element = response.complaints[i];
                    var template = $("#template .listComplaints li").clone();
                    
                    template.find('.author').append(element.author);
                    template.find('.category').append(element.category);
                    template.find('.cContent').append(element.content);
                    template.find('.createdAt').append(element.createdAt);
                    
                    var active = element.active ? 'activado' : 'desactivado';
                    template.find('.cActive').append(active);
                    
                    if(element.target){
                        template.find('.target').append(element.target);
                    }else{
                        template.find('.target').remove();
                    }
                    
                    $(".cont ul.listComplaints").append(template);
                }
                
                complaint.pageList++;
                if(response.addMore){
                    $("#addMore").show();
                }else{
                    $("#addMore").remove();
                }
                $("a.titleType").removeClass('loading');
                $("#addMore").removeClass('loading');
                $("ul.complaintType").removeClass('hidden');
            }
        }, function(error){
            console.error(error);
        });
        
        return false;
    }
};