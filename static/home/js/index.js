//var bodyHeight = document.body.clientHeight;

$(document).ready(function(){
    //media({
    //    musicSrc:"audio/way.mp3",
    //    musicPlaySrc:"images/mPlay.png",
    //    musicPauseSrc:"images/mPaused.png"
    //});
    //function stopScrolling( e ) {
    //    e.preventDefault();
    //}
    //document.addEventListener( 'touchmove' , stopScrolling , false );
    //关闭弹窗
    $(".close").on("click",function(){
        var Id =$(this).parents(".popup");
        close(Id)
    });
    //导航栏点击
    $("a#cur").on("click",function(){  
        showSectionCss("#cur");
        showSection("#indexPage")
    })
    $("a#sort").on("click",function(){
        showSectionCss("#sort");
        showSection("#sortPage")
    })
    $("a#search").on("click",function(){
        showSectionCss("#search");
        showSection("#searchPage")
    })
    $("a#cart").on("click",function(){
        //if(sysParam.havaGood==1){
        //    $(".cartNoneDiv").addClass("dis_none")
        //    $(".cartHaveDiv").removeClass("dis_none")
        //    showSection("#cartPage")
        //}else{
            $(".cartNoneDiv").removeClass("dis_none")
            $(".cartHaveDiv").addClass("dis_none")
            showSection("#cartPage");
            balanceNum();
        //}
    })

    $("a#my").on("click",function(){
        showSectionCss("#my");
        showSection("#myCenterPage")
    })
    //输入框失焦
    $('.inputBind').on("focus",function(){
        $("nav").hide();
    }).on("blur",function(){
        $("nav").show();
    });
    //保存公司信息按钮
    $(".storeCompanyInfo").on("click",function(){
        var name=$("input.companyNameInput").val()
        var tel=$("input.companyTelInput").val()
        var area=$("input.companyInput").val()
        var detail=$("input.companyDetailInput").val()

        if(name=='' || tel=='' || area==''||detail==''){
            alert("请填写完整信息")
        }else{
            alert("保存成功")
        }
    })

    //发票页面点击个人/公司
    $(".private").on("click",function(){
        $("private.img").attr("src","images/greencircle.png")
        $("enterprice.img").attr("src","images/circle.png")
    })
    $(".enterprice").on("click",function(){
        $("private.img").attr("src","images/circle.png");
        $("enterprice.img").attr("src","images/greencircle.png");
    })

    
    $("a.ic-cart").on("click",function(){
       //alert("成功加入购物车")

    })
    //性别选择
    $("p.wom").on("click",function(){
        $("img#wom").attr("src","images/greencircle.png")
        $("img#man").attr("src","images/circle.png")
    })
    $("p.man").on("click",function(){
        $("img#man").attr("src","images/greencircle.png")
        $("img#wom").attr("src","images/circle.png")
    })
    //点赞
    /*$(".dianzan>div>i").on("click",function(){
        var n=$(this).index()+1;
        $.ajax({
            type: 'post',
            url:'../../commentHandle',
            dataType: 'json',
            timeout: 15000,
            data: {
                num: n,
            },
            success: function (data) {
                if (data.status == 1) {
                    $(".dianzan>div>i").removeClass("zan");
                    setTimeout(function(){
                        for(var i=0;i<n;i++){
                            console.log(i)
                            $(".dianzan>div>i").eq(i).addClass("zan")
                        }
                    },10)
                } else {
                    alert(data.msg);
                }
            },
            error: function (xhr, type) {
                alert('网络超时，请刷新后再试！');
            }
        })
    })*/
    var guige=$(".goodsInfo p span.guigeNum");
    var guigeT=0;
    var gui = 0;
    for(var i=0;i<guige.length;i++){
        gui=parseInt($(".cartListDiv li").eq(i).find(".guigeNum").text());
            guigeT=guigeT+gui;
    }
  //  $("nav #cart span").text(guigeT);

    //页面加载
    var pageNum=1;
    var id=$(".listDiv").attr("data-id"),url,classId=$(".listDiv").attr("data-classId");
//    console.log('>>>' + id);
    switch (id){
        case "1": //限时抢购
            url='../market/limit_ajax';
            break;
        case "2": //一路领先
            url='../item/index_ajax/type/2';
            break;
        case "4": //精品推荐
            url='../item/index_ajax/type/4';
            console.log('>>>' + id);
            break;
        case "3": //积分享礼
            url='../score/index_ajax';
            break
        case "5":
        	//url = '../../../item/index_ajax';
        	url = '../item/index_ajax';//'{:U('item/index_ajax')}';
        	break;
        case "6":
        	url = '../item/index_ajax';//'{:U('item/index_ajax')}';
        	break;
    }

    $(".randLableDiv span").on("click",function(){
       var n=$(this).index();
        switch (n) {
            case 0: //销量优先
                    sortId=0;
                $(this).css("color","#f29200").show().siblings().css("color","#8f8c79");
                break;
            case 1://价格
                $(" .orderListDiv .randLableDiv span.priceLable").toggleClass("addToggle");
                if($(this).hasClass("addToggle")){
                    sortId=1;  //降序
                    $(this).css("color","#f29200").show().siblings().css("color","#8f8c79");
                }else{
                    sortId=2; //升序
                    $(this).css("color","#f29200").show().siblings().css("color","#8f8c79");
                }
                break;
            case 2://时间
                $(this).css("color","#f29200").show().siblings().css("color","#8f8c79");
                    sortId=3;
                break
        }
        $(".listDiv ul").html("");
        pageLoading(1,url,classId);
    });
    $(".orderListDiv .loadingMoreBtn").on("click",function(){
        var text=$(".loadingMoreBtn").text();
        if(text=="加载完了"){
            $(this).attr('disabled',true);
        }else{
            pageNum++;
            pageLoading(pageNum,url,classId);
        }
    })
});

var sortId=0;
function  pageLoading(pageNum,url,classId) {
    $.ajax({
        type: 'get',
        url:url,
        dataType: 'json',
        timeout: 15000,
        data: {
            sortId: sortId,  //排序的id
            p: pageNum,    //页数
            classId:classId   //分类id
        },
        success: function (data) {
            if (data.status == 1) {
                var html = '';
                html+=data.data.html;
                $(".listDiv ul").append(html);
                if(data.data.isfull == 0){ //pageNum==data.data.maxPage 修改为isfull 当为0时说明数据查询完毕 by jimyan 2015年10月19日21:21:391
                    $(".loadingMoreBtn").text("无更多商品了，可换个姿势搜索哦")
                }else{
                    $(".loadingMoreBtn").text("点我击加载更多")
                }
            } else {
                alert(data.msg);
            }
        },
        error: function (xhr, type) {
            //alert('网络超时，请刷新后再试！');
        }
    });
}
//弹框
function alertSelect(b) {
    document.onkeydown = function (event) {
        var e = event || window.event || arguments.callee.caller.arguments[0];
        switch (e.keyCode) {
            case 13:
                b.yesFun();
                off();
                break;
        }
    };
    $("#alertMsg").html("").append(b.msg);
    objshow("#alertBox");
    if (b.button == false) {
        $("#alertBtnNo,#alertBtnYes").hide();
    } else {
        $("#alertBtnNo,#alertBtnYes").show();
    }
    $("#alertBtnNo").on("click", function () {
        if (b.noFun) {
            b.noFun()
        }
        off()
    });
    $("#alertBtnYes").on("click", function () {
        if (b.yesFun) {
            b.yesFun()
        }
        off()
    });
    $("#alertBoxClose").on("click", function () {
        off();
    });
    function off() {
        $("#BtnNo,#alertBtnYes,#alertBoxClose").off();
        objhide("#alertBox");
    }
}
//返回按钮
function goBack()
{
	console.log("reload");
	 window.history.go(-1);
//    window.history.back();
//    console.log("reload");
//    location.reload();
}
function showSection(id){
    var n=$(this).index()
    $(id).css("z-index","9").show().siblings().css("z-index","8")
    $(id).eq(n).sibling().hide();
}
function showSectionCss(id){
    $(id).css({"background-color":"#f29200","color":"white"}).show().siblings().css({"background-color":"#fbf7f1","color":"#60563f"})
    //$(id).addClass("navAim")
}
function popupMT(Id){
    var h1,$boxItem=$(Id).find(".boxItem");
    h1 = $boxItem.height();
    $boxItem.css({marginTop:-h1/2});
}
function close(id){
    id.removeClass("show").addClass("hide");
    id.find(".boxItem").removeClass("zoomIn").addClass("zoomOut");
    setTimeout(function(){
        id.hide().addClass("show").removeClass("hide");
    },500)
}
function media(b) {
    var audio = new Audio(b.musicSrc), $music = $("#musicCtrl");
    audio.setAttribute("autoplay", "autoplay");
    audio.setAttribute("loop", "loop");
    document.onreadystatechange = function () {
        if (document.readyState == "complete" && audio.paused) {
            audioPlay();
        }
    };
    $("body").one("touchstart", function () {
        if (audio.paused) {
            audioPlay();
        }
    });
    $music.on("click", musicCtrl);
    function musicCtrl() {
        if (audio.paused) {
            audioPlay();
            return;
        }
        audioPause();
    }

    function audioPlay() {
        audio.play();
        $music.attr("src", b.musicPlaySrc);
        $music.addClass("mAnim");
    }

    function audioPause() {
        audio.pause();
        $music.attr("src", b.musicPauseSrc);
        $music.removeClass("mAnim");
    }
}

function buys(goodId,_this) {
    var quantity = 1;
    add_cart(goodId, quantity,_this);
}

//添加到购物车
function add_cart(goodId, quantity,_this)//商品ID，购买数量
{
    $.post(cartUrl, {goodId: goodId, quantity: quantity}, function (data) {
        $(".noneBtnAlertSpan").show()
        $(".noneBtnAlertSpan").text(data.msg);
        setTimeout(function(){
            $(".noneBtnAlertSpan").hide()
        },1000)
        if (data.status == 1) {
            if(data.status.result==2){//限购
                $(".noneBtnAlertSpan").show()
                $(".noneBtnAlertSpan").text(data.msg);
                setTimeout(function(){
                    $(".noneBtnAlertSpan").hide()
                },1000)
            }else{
                //购物车动画
                var $pointDiv = $('<div id="pointDivs">').appendTo('body');
                for(var i = 0;i<5;i++){
                    $('<div class="point-outer point-pre"><div class="point-inner"></div></div>').appendTo($pointDiv);
                }
                //获取开始点坐标
                //ev.target ====>
                var startOffset =  _this.offset();
                //获取结束点坐标
                var endTop = window.innerHeight - 140, endLeft = 430,left = startOffset.left+10,top = startOffset.top+10;
                var outer = $('#pointDivs .point-pre').first().removeClass("point-pre").css({left:left+'px',top:top+'px'});
                var inner = outer.find(".point-inner");
                setTimeout(function(){
                    outer[0].style.webkitTransform = 'translate3d(0,'+(endTop)+'px,0)';
                    inner[0].style.webkitTransform = 'translate3d('+(endLeft - left)+'px,0,0)';
                    setTimeout(function(){
                        outer.removeAttr("style").addClass("point-pre");
                        inner.removeAttr("style");
                    },1000);
                },1);
                //nav购物车的数量显示
                var guigeT= data.totalCnt;
                $("nav #cart span").text(guigeT);
                console.log(guigeT);
            }
        } else {
            $("#pointDivs").hide()
        }
    }, 'json');
}
////上下滑动插件
function contentMove(Id,max){
    var $moveDiv =$(Id),
        $move =$(Id+' .move'),
        min = $move.height(),
        offh = 0,
        old = 0,
        cur = 0,
        curr = 0,
        currr = 0;
    offh =  max - min;
    console.log(offh);
    $moveDiv.css({height:max+'px'});
    $moveDiv.off();
    setTimeout(function(){
        if(min>max){
            $moveDiv.on("touchstart",function(e){
                tstart(e)
            }).on("touchmove",function(e){
                tmove(e)
            }).on("touchend",function(){
                tend()
            });
        }else{
            $moveDiv.off()
        }
    },1);
    var tstart = function(e){
        $move.css({transition: 'none','-webkit-transition': 'none'});
        old = e.touches[0].pageY;
    },tmove = function(e){
        cur =  e.touches[0].pageY - old;
        curr = currr + cur;
        $move.css({transform: 'translateY('+curr+'px)', '-webkit-transform': 'translateY('+curr+'px)' });
    },tend = function(){
        $move.css({transition: 'all 0.4s','-webkit-transition': 'all 0.4s'});
        currr = curr;
        if(currr>=0&&cur>0){
            currr=0;$move.css({transform: 'translateY(0px)', '-webkit-transform': 'translateY(0px)' });
        }else if(currr<=offh){
            $move.css({transform: 'translateY('+offh+'px)', '-webkit-transform': 'translateY('+offh+'px)' });
            currr = offh
        }
    }


}