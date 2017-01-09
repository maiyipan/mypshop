$(function(){
    //顶部菜单点击
    $('#J_tmenu a').on('click', function(){
        var data_id = $(this).attr('data-id');
        var data_name = $(this).attr('name');
        //改变样式
        $(this).parent().addClass("on").siblings().removeClass("on");
        //改变左侧
        $('#sidebar').load($('#sidebar').attr('data-uri'), {menuid:data_id}, function(response,status,xh){
        	/*console.log(response);
        	console.log(status);
          	console.log(xh);*/
        	
        });
        //显示左侧菜单，当点击顶部时，展开左侧
        $('#sidebar').parent().removeClass('left_menu_on');
        $('html').removeClass('on');
        $('#J_lmoc').removeClass('close').data('clicknum', 0);
        $('#breadcrumbs ul li').eq(0).html(data_name);
    });
    
    $("li.top_menu").first().children().trigger("click"); 
});
