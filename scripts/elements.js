const palletSelect=document.querySelectorAll('.input_gpt')
const authorsDrop =document.querySelectorAll(".author_input")
const pageBackground = document.getElementById("wpbody-content");
const sidebar= document.getElementById("sidebar")
const gpt_switch= document.querySelectorAll(".gpt_label_model")
const switch_container=document.getElementById("chatgpt_model")
const gpt_icons =document.querySelectorAll(".gpt_dash_icos")
const authors_select =document.querySelector(".autor_select")
const categoty_select= document.querySelector(".category_select")
const authors_item=document.querySelector(".authors_item")
const category_items = document.querySelector(".category_item")
const schedule_radio_button = document.querySelector(".schedulee")
const gpt_textareas= document.querySelectorAll(".chat_textarea")
const selection_items=document.querySelectorAll(".selection_item")
const api_config =document.querySelector(".api_config")
const gpt_config =document.querySelector(".chat_gpt_vonfiguration")

api_config.addEventListener('click',()=>{
  gpt_config.classList.toggle("show")
})

palletSelect.forEach((e,i)=>{
    e.addEventListener('change',()=>{
        if(e.getAttribute("name")=="DARK"){
            pageBackground.classList.add("dark")
            sidebar.classList.add("sidebar-dark")
            switch_container.classList.add("sidebar-dark")
            gpt_textareas.forEach((e)=>{
                e.classList.add("dark")
            })
            selection_items.forEach((e)=>{
                e.classList.add("arrow_white")
            })
        }else{
            pageBackground.classList.remove("dark")
            sidebar.classList.remove("sidebar-dark")
            switch_container.classList.remove("sidebar-dark")
            gpt_textareas.forEach((e)=>{
                e.classList.remove("dark")
            })
            selection_items.forEach((e)=>{
                e.classList.remove("arrow_white")
            })
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

categoty_select.addEventListener('click',()=>{
  category_items.classList.toggle("show")
})

authorsDrop.forEach((e,i)=>{
    e.addEventListener('change',()=>{
        authorsDrop.forEach((j,i)=>{
            if(j!==e){
                j.checked=false;
            }
        })
    })
})


schedule_radio_button.addEventListener('change',()=>{
        if(schedule_radio_button.checked){
            alert('go')
        }
})
    