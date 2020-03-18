<style>
    .grid-content {
        border-radius: 4px;
        min-height: 36px;
        font-size:18px;
        text-align: center;
    }
    .grid-content:hover{
        background: #20a0ff;
        transition: background-color .6s;
        cursor:pointer;
    }
</style>
<template>
    <div class="header">
        <div class="logo">{{ use_title }}</div>
        <el-row>
            <template v-for="item in items">
                <el-col :span="2" v-bind:id="item.id" >
                    <div class="grid-content" @click="setMenuGroup(item.id)">{{ item.name }}</div>
                </el-col>
            </template>
            <div class="user-info">
                <el-dropdown trigger="click" @command="handleCommand">
                    <span class="el-dropdown-link">
                        <img class="user-logo" src="../../../static/img/img.jpg">
                        {{username}}
                    </span>
                    <el-dropdown-menu slot="dropdown">
                        <el-dropdown-item command="loginout" @click="logout()">退出</el-dropdown-item>
                    </el-dropdown-menu>
                </el-dropdown>
            </div>        
        </el-row>
    </div>
</template>
<script>
    export default {
        data() {
            return {
                name: 'linxin',
                group_menu_id: '',
                items: {},
                use_title:'后台管理系统'
            }
        },
        created: function () {
            this.getlist();
            let wechat_name = localStorage.getItem('use_wechat_name');
            if(wechat_name){
                this.use_title = wechat_name;
            }
        },
        computed:{
            username(){
                let username = localStorage.getItem('username');
                return username ? username : this.name;
            }
        },
        methods:{
            handleCommand(command) {
                if(command == 'loginout'){
                    localStorage.removeItem('username');
                    localStorage.removeItem('user');
                    this.$router.push('/login');
                }
            },
            getlist(){
                const self = this;
                //获取头部导航
                self.$ajax.post(self.$interfase.titleMenuList).then(function (response) {
                    if(response.data.status === '000'){
                        self.items = response.data.data;
                    }
                });
            },
            setMenuGroup(id){
                const self = this;
                let info = {'id':id}
                //设置当前头部导航
                self.$ajax.post(self.$interfase.setMenuGroup,info).then(function (response) {
                    if(response.data.status === '000'){
                        location.reload();
                    }
                });
            },
            logout(){
                const self = this;
                //设置当前头部导航
                self.$ajax.get(self.$interfase.logout).then(function (response) {
                    if(response.data.status === '000'){
                        // self.group_menu_id = id;
                        // localStorage.setItem('left_menu',id);
                    }
                });
            },
        }
    }
</script>
<style scoped>
    .header {
        position: relative;
        box-sizing: border-box;
        width: 100%;
        height: 70px;
        font-size: 22px;
        line-height: 70px;
        color: #fff;
    }
    .header .logo{
        float: left;
        width:250px;
        text-align: center;
    }
    .user-info {
        float: right;
        padding-right: 50px;
        font-size: 16px;
        color: #fff;
    }
    .user-info .el-dropdown-link{
        position: relative;
        display: inline-block;
        padding-left: 50px;
        color: #fff;
        cursor: pointer;
        vertical-align: middle;
    }
    .user-info .user-logo{
        position: absolute;
        left:0;
        top:15px;
        width:40px;
        height:40px;
        border-radius: 50%;
    }
    .el-dropdown-menu__item{
        text-align: center;
    }
</style>
