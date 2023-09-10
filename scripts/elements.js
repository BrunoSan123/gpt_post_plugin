const palletSelect=document.querySelectorAll('.input_gpt')
const pageBackground = document.getElementById("wpbody-content");
const sidebar= document.getElementById("sidebar")
const gpt_switch= document.querySelectorAll(".gpt_label_model")
const switch_container=document.getElementById("chatgpt_model")
const gpt_icons =document.querySelectorAll(".gpt_dash_icos")
const authors_select =document.querySelector(".autor_select")
const authors_item=document.querySelector(".authors_item")

palletSelect.forEach((e,i)=>{
    e.addEventListener('change',()=>{
        if(e.getAttribute("name")=="DARK"){
            pageBackground.classList.add("dark")
            sidebar.classList.add("sidebar-dark")
            switch_container.classList.add("sidebar-dark")
        }else{
            pageBackground.classList.remove("dark")
            sidebar.classList.remove("sidebar-dark")
            switch_container.classList.remove("sidebar-dark")
        }
        palletSelect.forEach((j,i)=>{
            if(j!==e){
                j.checked=false;
            }

        })

    })

})

gpt_switch.forEach((e,i)=>{
    e.addEventListener('change',(i)=>{
       if(i.target.getAttribute("data-target")=="ray"){
        gpt_icons[0].childNodes[0].style="fill:blue !important"
       }
    })
})

authors_select.addEventListener('click',()=>{
   authors_item.classList.toggle("show")
})
