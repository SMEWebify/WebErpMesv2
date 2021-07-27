		
		//user nav bar switch div
		function openDiv(evt, divId) {
			var i, tabcontent, tablinks;
			tabcontent = document.getElementsByClassName("tabcontent");
			for (i = 0; i < tabcontent.length; i++) {
			tabcontent[i].style.display = "none";
			}
			tablinks = document.getElementsByClassName("tablinks");
			for (i = 0; i < tablinks.length; i++) {
				tablinks[i].className = tablinks[i].className.replace(" active", "");
			}
			document.getElementById(divId).style.display = "block";
			evt.currentTarget.className += " active";
		}
		
		//use for pring document page
		function imprimer_page(){
			window.print();
		}

		//use for display menu
		window.onload=function(){
			document.getElementById("defaultOpen").click();
			var OpenNav = document.getElementById("OpenNav");
			var ClosenNav = document.getElementById("ClosenNav");
			OpenNav.addEventListener("click", openNav);
			ClosenNav.addEventListener("click", closeNav);

			var close = document.getElementsByClassName("closebtnligne");
			var i;
	
				for (i = 0; i < close.length; i++) {
					close[i].onclick = function(){
						var div = this.parentElement;
						div.style.opacity = "0";
						setTimeout(function(){ div.style.display = "none"; }, 600);
					}
				}
		}
		
		function openNav() {
			document.getElementById("myNav").style.width = "100%";
		}
		function closeNav() {
			document.getElementById("myNav").style.width = "0%";
		}

		//script from w3school Use for filter datalist
		function myFunction() {
			// Declare variables
			var input, filter, ul, li, a, i, txtValue;
			input = document.getElementById('myInput');
			filter = input.value.toUpperCase();
			ul = document.getElementById("myUL");
			li = ul.getElementsByTagName('li');
	
			for (i = 0; i < li.length; i++) {
				a = li[i].getElementsByTagName("a")[0];
				txtValue = a.textContent || a.innerText;
				if (txtValue.toUpperCase().indexOf(filter) > -1) {
				li[i].style.display = "";
				} else {
				li[i].style.display = "none";
				}
			}
		}

		function ShowTechnicalCut(TrKey) {
			var x = document.getElementById("TechnicalCutRow"+ TrKey);
			if (x.style.display === "none") {
			  x.style.display = "block";
			} else {
			  x.style.display = "none";
			}
		}
		

		//for display passord
		function DisplayPassword() {
			var x = document.getElementById("mdp");
			if (x.type === "password") {
			  x.type = "text";
			} else {
			  x.type = "password";
			}
		  }

