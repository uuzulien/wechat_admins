<style>
    .el-card__header{
        padding: 5px;
        background-color: #000000;
        border-bottom: 0;
    }
    .el-card__body{
        padding: 0;
    }
    .clearfix{
        color: #FFF;
        font-size: 12px;
    }
    .page_title{
        height:50px;
        background-color: #403F3F;
        color: #FFF;
        line-height: 50px;
        font-family: 黑体;
    }
    .page_content{
        height:450px;
    }
    .page_footer{
        border-top: 1px solid #d1dbe5;
        height:50px;
        line-height: 50px;
    }
</style>
<template>
    <div class="row">
        <el-row>
            <el-col :span="8" :offset="16">
                <el-button type="primary" @click="handleDialog()">客服链接统改</el-button>
            </el-col>
        </el-row>
        <el-row>
            <el-col :span="5">
                <el-card class="box-card">
                    <!--手机头-->
                        <div slot="header" class="clearfix">
                            <el-row>
                                <el-col :span="7">
                                <span>●●●●●</span>
                                <span>WeChat</span>
                            </el-col>
                            <el-col :span="4" :offset="3">
                                <span>{{ time }}</span>
                            </el-col>
                            <el-col :span="5" :offset="5">
                                <span>100%</span>
                                <span>▂▃▅▆</span>
                            </el-col>
                            </el-row>
                        </div>
                    <el-row class="page_title">
                        <el-col :span="6">
                            <span>&nbsp＜返回</span>
                        </el-col>
                        <el-col :span="4" :offset="4">
                            <span>&nbsp{{ wechat_name }}</span>
                        </el-col>
                        <el-col :span="2" :offset="8">
                            <i class="el-icon-star-on"></i>
                        </el-col>
                    </el-row>
                    <el-row class="page_content"></el-row>
                    <el-row class="page_footer">
                        <el-row>
                            <el-col :span="2" :offset="1">
                                <i class="el-icon-menu"></i>
                            </el-col>
                            <el-col :span="21" class="menus">
                                <el-col :span="8">2</el-col>
                            </el-col>
                        </el-row>
                    </el-row>
                </el-card>
            </el-col>
        </el-row>

        <!--客服链接调整-->
        <el-dialog :visible.sync="linkDialog" title="客服链接调整">
            <el-form label-width="140px" :model="formInfo" ref="formInfo" inline>
                <el-row>
                    <el-col :span="24">
                        <el-form-item label="链接" prop="name">
                            <el-input style="width: 410px" v-model="formInfo.url" ></el-input>
                        </el-form-item>
                    </el-col>
                </el-row>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button type="primary" @click="handleAjax(formInfo,'当前匹配第二菜单中第二栏目的客服链接，请问是否替换？')">替换</el-button>
            </div>
        </el-dialog>

    </div>
</template>
<script>
    export default {
        data:function(){
            return {
                linkDialog:false,
                formInfo:{
                    url:''
                },
                time:'test time',
                wechat_name:'test name',
                wechat_name:'test name',
            }
        },
        methods:{
            //公众号菜单列表获取
            show_menus:{

            },
            //处理统改链接
            handleAjax(info,message){
                let  self = this;
                var url = self.$interfase.set_service_img_url;
                self.$confirm(message || '确定执行该操作吗？', '替换操作', {
                    type: 'warning'
                }).then(function() {
                    self.$ajax.post(url, info||{}).then(function(response) {
                        if(response.data.status == '000'){
                            self.groupFormDialog = false;
                            self.$message({
                                message: '替换成功',
                                type: 'success'
                            });
                        }
                    })
                }).catch(function(){

                })
            },
            handleDialog(){
                this.linkDialog = true;
            }
        }
    }
</script>