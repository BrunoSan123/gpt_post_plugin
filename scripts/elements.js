const palletSelect=document.querySelectorAll('.input_gpt')
const pageBackground = document.getElementById("wpbody-content");
const sidebar= document.getElementById("sidebar")

palletSelect.forEach((e,i)=>{
    e.addEventListener('change',()=>{
        if(e.getAttribute("name")=="DARK"){
            pageBackground.classList.add("dark")
            sidebar.classList.add("sidebar-dark")
        }else{
            pageBackground.classList.remove("dark")
            sidebar.classList.remove("sidebar-dark")
        }
        palletSelect.forEach((j)=>{
            if(j!==e){
                j.checked=false;
            }
        })

    })
})