const page_auto_post =document.body.classList.contains("settings_page_chatgpt_plugin")

if(page_auto_post){
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
const schedule_container= document.getElementById("schedule_datetime_container")
const shcedule_input = document.querySelectorAll(".schedule_input")
const upload_image= document.querySelector(".upload-image")
const upload_buton = document.querySelector("#image_upload")
const imageButtons= document.querySelectorAll(".ia_image_input")
const new_category_input= document.querySelector(".new_category")
const category_input_text=document.querySelector(".modal_category")


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
  
  
  shcedule_input.forEach((e)=>{
      e.addEventListener('click',()=>{

        if(schedule_radio_button.checked){
              schedule_container.classList.add("show")
          }else{
              schedule_container.classList.remove("show")
          }

      })

  })

  imageButtons.forEach((e)=>{
    e.addEventListener('change',()=>{
        if(upload_image.checked){
            upload_buton.classList.add("show")
          }else{
            upload_buton.classList.remove("show")
          }
        imageButtons.forEach((j)=>{
            if(j!==e){
                j.checked=false;
            }
        })
    })
  })

   new_category_input.addEventListener('click',()=>{
    category_input_text.classList.toggle("show")
  }) 
  

}








    