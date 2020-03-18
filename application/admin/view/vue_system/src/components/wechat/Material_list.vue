<style>
    .time {
        font-size: 13px;
        color: #999;
    }

    .bottom {
        margin-top: 13px;
        line-height: 12px;
    }

    .button {
        padding: 0;
        float: right;
    }

    .image {
        width: 100%;
        display: block;
    }

    .clearfix:before,
    .clearfix:after {
        display: table;
        content: "";
    }

    .clearfix:after {
        clear: both;
    }
    .clearfix{
        background: #000000;
        opacity:0.2;
    }
    .font_style{
        color: #97a8be;
    }
</style>
<template>
    <div class="row">
        <el-tabs v-model="check_tab" @tab-click="get_bind_list(check_tab)">
            <el-tab-pane label="图文素材" name="news">
                <el-row>
                    <el-col :span="6" :offset="18">
                        <el-button type="primary" @click="syn_wechat('news')">同步微信</el-button>
                    </el-col>
                </el-row>
                <el-row>&nbsp</el-row>
                <el-row>
                    <el-col :span="4" v-for="item in bind_list" style="padding: 14px;" :offset="1">
                        <el-card>
                            <div style="padding: 14px;border-bottom: 0.2px solid #dddddd;" v-for="items in item.data" :key="items.id">
                                <img height="78px" :src="get_img(items.news_thumb_url)" class="image">
                                <div class="bottom font_style">
                                    <span>{{ items.news_title }}</span>
                                </div>
                            </div>
                            <el-col style="padding: 6px;" :offset="14">
                                <el-button type="primary" @click="handleAjax('news',{id:item.id,media_id:item.media_id},'确定删除图文<'+item.media_id+'>吗？')">删除</el-button>
                            </el-col>
                        </el-card>
                    </el-col>
                </el-row>
            </el-tab-pane>
            <el-tab-pane label="图片" name="image">
                <el-row>
                    <el-col :span="6" :offset="18">
                        <el-button type="primary" @click="syn_wechat('image')">同步微信</el-button>
                    </el-col>
                </el-row>
                <el-row>&nbsp</el-row>
                <el-row>
                    <el-col :span="4" v-for="item in bind_list" style="padding: 14px;" :offset="1">
                        <el-card>
                            <img height="120px" :src="get_img(item.link_url)" class="image">
                            <el-col style="padding: 6px;" :offset="14">
                                <el-button type="primary" @click="handleAjax('not_news',{id:item.id,media_id:item.media_id},'确定删除图片<'+item.media_id+'>吗？')">删除</el-button>
                            </el-col>
                        </el-card>
                    </el-col>
                </el-row>
            </el-tab-pane>
            <el-tab-pane label="语音" name="voice">
                <el-row>
                    <el-col :span="6" :offset="18">
                        <el-button type="primary" @click="syn_wechat('voice')">同步微信</el-button>
                    </el-col>
                </el-row>
                <el-row>&nbsp</el-row>
                <el-row>
                    <el-col :span="4" v-for="item in bind_list" style="padding: 14px;" :offset="1">
                        <el-card>
                            <img height="120px" :src="get_img(item.link_url)" class="image">
                            <el-col style="padding: 6px;" :offset="14">
                                <el-button type="primary" @click="handleAjax('not_news',{id:item.id,media_id:item.media_id},'确定删除语音<'+item.voice_name+'>吗？')">删除</el-button>
                            </el-col>
                        </el-card>
                    </el-col>
                </el-row>
            </el-tab-pane>
            <el-tab-pane label="视频" name="fourth">
                暂不添加视频
            </el-tab-pane>
        </el-tabs>
    </div>
</template>
<script>
    export default {
        data:function () {
            return {
                check_tab:'',
                bind_list:[]
            }
        },
        methods:{
            get_bind_list(type){
                let self = this;
                let info = {'file_type':type}
                self.$ajax.post(self.$interfase.get_material_list,info).then(function (response){
                    if(response.data.status == '000'){
                        self.bind_list = response.data.data.list;
                    }else{

                    }
                });
            },
            get_img(url){
                let self = this;
                return 'http://wechatadmins.weijuli8.com/admin/show_wechat_img?url='+ url;
            },
            //同步微信内容
            syn_wechat(type){
                let self = this;
                let info = {'type':type}
                self.$ajax.post(self.$interfase.syn_wechat,info).then(function (response){
                    if(response.data.status == '000'){

                    }
                });
            },
            handleAjax: function(go, info, message) {
                let  self = this;
                var url = self.$interfase.del_material;
                self.$confirm(message || '确定执行该操作吗？', '提示', {type: 'warning'}).then(function() {
                    self.$ajax.post(url, info||{}).then(function(response) {
                        if(response.data.status == '000'){
                            self.$message({
                                message:'操作成功',
                                type:'success'
                            });
                        }
                    })
                }).catch(function(){})
            }
        }
    }
</script>