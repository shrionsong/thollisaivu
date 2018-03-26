<html>
<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type" /> 
<title>தொல்லிசைவு சோதனை</title>
<link rel="stylesheet" href="paani.css" />
</head>
<body>
	<div class="main">
		<div class="header clear">
			<div class="title">தொல்லிசைவுச் சோதனை</div>
			<div class="logo"></div>
			<div class="logo1"></div>
		</div>
		<div class="content">
			<div class="subheader">தங்கள் உரை தொல்காப்பிய விதிகளுக்கு உடன்பட்டுள்ளதா?</div>
			<div class="description">தாங்கள் சோதிக்க வேண்டிய உரையை கீழ்காணும்
				உரைபெட்டியில் உள்ளிடுங்கள். உங்கள் உரையை கோப்பாகவும் கீழேயுள்ள
				கோப்பேற்றி வழியாக ஏற்றலாம். பிறகு தொல்காப்பிய விதிகளுக்கு தங்கள் உரை இசைவாக உள்ளதா என்று சோதிக்க, வலது
				புறமுள்ள 'சோதி' விசையை சொடுக்கவும்.</div>
			<div id='form' class="clear">
				<div class="field">
					<label>சோதிக்க வேண்டிய உரை அல்லது கோப்பு:</label>
					<textarea id='qtext' name="qtext"></textarea>
					<input type="file" id="qfile" name="qfile" disabled="disabled" />
				</div>
				<div class="field btn">
					<input id="btnCheck" type="button" class="button" value="சோதி" /> <input
						id="btnClear" type="button" class="button" value="அழி" />
				</div>
			</div>
			<div id="status"></div>
			<div id="results" class="clear">
				<div class='subheader'>சோதனை முடிவுகள்</div>
				<div><span class='kennam'></span></div>
				<div class='clear'>
				<div class='col' id='matches'></div>
				<div class='col' id='details'></div>
				</div>
				<div id="info" style="display:none;">
					<label></label><span class='ennam'></span>
				</div>
				<div id='arrow'></div>
			</div>
			<div class="footer"></div>
		</div>
	</div>

	<script type="text/javascript" src="jquery-3.2.1.min.js"></script>
	<script type="text/javascript">

	function kalavuNiram(tKalaven,ptKalaven){
		f = tKalaven/ptKalaven;
		return "rgb(" + (f<.5 ? Math.floor(2*f*50+205) : 255) + "," + (f>.5 ? Math.floor(2*(1-f)*50+205) : 255) + ",205)";
	}
	
	function kEn(tKalaven,ptKalaven){
		return Math.floor(tKalaven/ptKalaven * 10);
	}
	
	function toggle_results(show){
		if(show==null) show=false;
		if(show){
			$("#results").addClass("available");
		}else{
			$("#results").removeClass("available");
		}
	}
	
	function unpin(e){
		e.removeClass("pinned");
	}
		
	function pin(e){
		$("span.s.pinned").removeClass("pinned");
		e.addClass("pinned");
		showDetails(e.attr("did"));
	}
	
	function showDetails(kid){
		$("#details").find(".d").each(function(){
			if(kid==$(this).prop("id")){
				$(this).addClass("active");
			}else{
				$(this).removeClass("active");
			}
		});
	}

	$(document).ready(function(){
/*
		$(window).resize(function(d){
			$(".dimensions").text($(window).width() + "x" + $(window).height());
		});
*/
		if($(document).height() > $(".main").height()){
			$("#results").css("min-height",$(document).height()-$(".main").height());
		}
		
		toggle_results(false);
/*
		$(document).on("mousemove","span.s",function(e){
			i = $("#info");
			i.find(".ennam").text($(this).attr("data-vithi"));
			i.css({
				left:(e.pageX+15)+"px",
				top:e.pageY+"px"
			});
			i.appendTo(this);
			i.show();
		});
*/
		$(document).on("mouseenter","span.s",function(e){
			if($("span.s.pinned").length==0){
				showDetails($(this).attr("did"));
			}
		});

		$(document).on("mouseleave","span.s",function(){
			$("#info").hide();
		});
		
		$(document).on("click","span.s",function(){
			if($(this).is(".pinned"))
				unpin($(this));
			else
				pin($(this));
		});

		$(document).on("click","#btnClear",function(){
			$("#qtext").text("").val("");
		});
		
		$(document).on("click",".d a[url]",function(){
			window.open($(this).attr("url"),"detail");
		});
		
		$(document).on("click","#btnCheck",function(){
			toggle_results(false);
			$("#results").append($("#info"));
			$("#matches,#details").empty();
			$("#status").text("உரை சோதிக்கப்படுகிறது...");
			qtext = ($("#qtext").val()==""?$("#qtext").text():$("#qtext").val());

			if(qtext==""){
				$("#status").text("சோதிக்க உரை எதுவும் உள்ளிடப்படவில்லை. தயை கூர்ந்து உரையை உள்ளிட்டு பிறகு முயலவும்");
				return;
			}
			
			$.post(
				"services/thollisaivu.php",
				{"qtext": qtext},
				function(data){
					console.log(data);
					var result = JSON.parse(data);
					if(result.success){
						$("#status").text("உரை வெற்றிகரமாக சோதிக்கப்பட்டது. முடிவுகளை கீழே காணவும்");
						$(".pennam").text(result.katturai.pizhayEn);
						var dr = $("#matches");
						var dt = $("#details");
						var cnt = 1;
						for(i=0;i<result.katturai.patthigal.length;i++){
							dr.append("<p>");
							for(j=0;j<result.katturai.patthigal[i].varigal.length;j++){
								dr.append("<span>");
								for(k=0;k<result.katturai.patthigal[i].varigal[j].thodargal.length;k++){
									thodar = result.katturai.patthigal[i].varigal[j].thodargal[k];
									dr.append("<span>");
									for(l=0;l<thodar.sorkal.length;l++){
										sol = thodar.sorkal[l];
										niram = (sol.pizhayEn>0?"#faa":(sol.vithigal.length>0?"#afa":"none"));
										//ken = kEn(thodar.kalaven,result.katturai.ptKalaven);
										dr.append("<span class='s' did='d" + cnt + "' data-pizhai='" + sol.pizhayEn + "' style='background-color:" + niram + "'>" + sol.patham + "</span>");
										d = $("<div class='d' id='d"+ cnt +"'></div>").appendTo(dt);
										for(m=0;m<sol.vithigal.length;m++){
											vithiEn = sol.vithigal[m];
											vithi = result.vithigal[vithiEn];
											d.append("<div style='background-color:" + (vithi.sari===false?"#fcc":"none") + "'><span class='name'>" + vithi.paa.athikaram + ' - ' + vithi.paa.iyal + "</span><br/><span class='paa'>" + vithi.paa.varigal.join('<br>') + "</span><br/><span class='snippet'>" + vithi.vilakkam + "</span></div>");	
										}
										cnt++;
									}
								}
								dr.append("</span>");
							}
							dr.append("</p>");
						}
						
						toggle_results(true);
						
					}else{
						$("#status").text("உரையை சோதிக்க முடியவில்லை");
						toggle_results(false);
					}
					console.log(result.status);
					
				}
			);
		});

		
		$(document).on("click","#btnSearch",function(){
			//toggle_results(false);
			$("#matches").empty();
			$("#status").text("உரை சோதிக்கப்படுகிறது...");
			qtext = ($("#qtext").val()==""?$("#qtext").text():$("#qtext").val());
			$.post(
				"services/plagcheck.php",
				{"qtext": qtext},
				function(data){
					var result = JSON.parse(data);
					$("#status").text(result.count + " பொருத்தங்கள் கண்டெடுக்கப்பட்டன. " + result.msg + "");
					if(result.success){
						var results = $("#matches");		

						for(i=0;i<result.matches.length;i++){
							match = result.matches[i];
							x = "<div>";
							x += "<label><b>" + match.name + '</b></label><br/><a href="' + match.url + '">' + match.dispUrl + '</a><br/>' + match.snippet + "<br/><br/>";
							x += "</div>";
							results.append(x);
						}
						
						toggle_results(true);
						
					}else{
						toggle_results(false);
					}
					console.log(result);
					
				}
			);
		});

		
	});
</script>
</body>
</html>
