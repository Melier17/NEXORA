const sidebar=document.getElementById('sidebar');
const menuToggle=document.getElementById('menuToggle');
menuToggle?.addEventListener('click',()=>sidebar.classList.toggle('open'));
document.addEventListener('click',event=>{if(window.innerWidth<=700&&sidebar.classList.contains('open')&&!sidebar.contains(event.target)&&!menuToggle.contains(event.target))sidebar.classList.remove('open')});

const modal=document.getElementById('taskModal');
const titleInput=document.getElementById('taskTitle');
const openModal=()=>{modal.hidden=false;titleInput.focus()};
const closeModal=()=>{modal.hidden=true};
document.getElementById('newTaskBtn').addEventListener('click',openModal);
document.getElementById('closeModal').addEventListener('click',closeModal);
document.getElementById('cancelModal').addEventListener('click',closeModal);
modal.addEventListener('click',event=>{if(event.target===modal)closeModal()});

document.getElementById('taskForm').addEventListener('submit',event=>{
  event.preventDefault();
  const title=titleInput.value.trim();
  if(!title)return;
  const project=document.getElementById('taskProject').value;
  const row=document.createElement('div');
  row.className='task-row';
  row.dataset.state='todo';
  const name=document.createElement('span');name.className='task-name';
  const circle=document.createElement('i');circle.className='check-circle';
  const label=document.createTextNode(title);name.append(circle,label);
  const projectName=document.createElement('span');projectName.className='task-project';projectName.textContent=project;
  const priority=document.createElement('span');const priorityTag=document.createElement('em');priorityTag.className='priority medium';priorityTag.textContent='Media';priority.append(priorityTag);
  const state=document.createElement('span');const stateTag=document.createElement('em');stateTag.className='task-state pending';stateTag.textContent='Pendiente';state.append(stateTag);
  row.append(name,projectName,priority,state);
  document.getElementById('taskRows').prepend(row);
  titleInput.value='';closeModal();applyFilter(currentFilter);
});

let currentFilter='all';
function applyFilter(filter){currentFilter=filter;document.querySelectorAll('.task-row').forEach(row=>{row.hidden=filter!=='all'&&row.dataset.state!==filter})}
document.querySelectorAll('.filter-btn').forEach(button=>button.addEventListener('click',()=>{document.querySelectorAll('.filter-btn').forEach(item=>item.classList.remove('selected'));button.classList.add('selected');applyFilter(button.dataset.filter)}));

document.querySelectorAll('.nav-link').forEach(link=>link.addEventListener('click',()=>{
  document.querySelectorAll('.nav-link').forEach(item=>item.classList.remove('active'));
  link.classList.add('active');
  document.getElementById('crumb').textContent=link.textContent.trim();
  if(window.innerWidth<=700)sidebar.classList.remove('open');
}));
