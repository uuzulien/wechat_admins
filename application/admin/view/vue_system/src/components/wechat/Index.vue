<template>
    <div class="row">
        <el-row :gutter="20">
            <el-col :span="6">
                <el-card class="box-card">
                    新增用户：{{ wechat_yestoday_data.new_user}}
                </el-card>
            </el-col>
            <el-col :span="6"><el-card class="box-card">取关用户：{{ wechat_yestoday_data.cancel_user }}</el-card></el-col>
            <el-col :span="6"><el-card class="box-card">净关注：{{ wechat_yestoday_data.in_user }}</el-card></el-col>
            <el-col :span="6"><el-card class="box-card">总用户：{{ wechat_yestoday_data.cumulate_user }}</el-card></el-col>
        </el-row>
    </div>
</template>

<script>
    export default{
        data:function () {
            return {
                wechat_yestoday_data:{
                    'ref_date' : 0,
                    'new_user' : 0,
                    'cancel_user' : 0,
                    'cumulate_user' : 0,
                    'in_user' : 0,
                },
                fans_num_list:[]
            }
        },
        created:function(){
            this.get_yestoday_info();
        },
        methods:{
            get_yestoday_info(){
                let self = this;
                self.$ajax.post(self.$interfase.get_yestoday_info).then(function (response){
                    if(response.data.status == '000'){
                        /*response.data.data.usersummary.forEach((item) => {
                            console.log(item);
                            return;
                            if(item.user_source == 1){
                                var name = '公众号搜索';
                            }else if(item.user_source == 17){
                                var name = '名片分享';
                            }else if(item.user_source == 30){
                                var name = '扫描二维码';
                            }else if(item.user_source == 43){
                                var name = '图文页右上角菜单';
                            }else if(item.user_source == 51){
                                var name = '支付后关注';
                            }else if(item.user_source == 57){
                                var name = '图文页内公众号名称';
                            }else if(item.user_source == 75){
                                var name = '公众号文章广告';
                            }else if(item.user_source == 78){
                                var name = '朋友圈广告';
                            }else{
                                var name = '其他合计';
                            }
                            fans_num_list.push({name:name,sum:item.new_user});
                        });*/
                        console.log(response.data);
                        self.wechat_yestoday_data.ref_date = response.data.data.usercumulate[0].ref_date;
                        self.wechat_yestoday_data.new_user = response.data.data.usercumulate[0].new_user;
                        self.wechat_yestoday_data.cancel_user = response.data.data.usercumulate[0].cancel_user;
                        self.wechat_yestoday_data.cumulate_user = response.data.data.usercumulate[0].cumulate_user;
                        self.wechat_yestoday_data.in_user = response.data.data.usercumulate[0].new_user -  response.data.data.usercumulate[0].cumulate_user;
                    }
                });
            }
        }
    }
</script>