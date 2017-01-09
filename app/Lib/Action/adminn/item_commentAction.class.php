<?php
/**
 * 商品评论
 */
class item_commentAction extends backendAction
{
    public function _initialize() {
        parent::_initialize();
        $this->_mod = M('item_comment');
    }
    
    public function index() {
        $prefix = C(DB_PREFIX);

        if ($this->_request("sort", 'trim')) {
            $sort = $this->_request("sort", 'trim');
        } else {
            $sort = $prefix.'item_comment.id';
        }
        if ($this->_request("order", 'trim')) {
            $order = $this->_request("order", 'trim');
        } else {
            $order = 'DESC';
        }

        $p = $this->_get('p','intval',1);
        $this->assign('p',$p);
        
        $where[$prefix.'item_comment.uid'] = array('in', $this->getUidList());
        $keyword = $this->_request('keyword','trim','');
        $keyword && $where['_string'] .= " ((".$prefix."user.username LIKE '%".$keyword."%')  OR (".$prefix."item_comment.info LIKE '%".$keyword."%') )"; //OR (".$prefix."item.title LIKE '%".$keyword."%')
        $search = array();
        $keyword && $search['keyword'] = $keyword;
        $this->assign('search',$search);
        
//         $re_time = $this->_request('re_time','trim','');
//         $re_time && $where .= ' AND re_time '.$re_time;
        $count = $this->_mod->join($prefix.'user ON '.$prefix.'user.id='.$prefix.'item_comment.userid')->join($prefix.'item ON '.$prefix.'item.id='.$prefix.'item_comment.item_id')->where($where)->count($prefix.'item_comment.id');
        $pager = new Page($count,20);
        $list  = $this->_mod->field($prefix.'item_comment.*,'.$prefix.'user.username')
        			->join($prefix.'user ON '.$prefix.'user.id='.$prefix.'item_comment.userid')
        				->join($prefix.'item ON '.$prefix.'item.id='.$prefix.'item_comment.item_id')
        					->where($where)->order($sort . ' ' . $order)
                               ->limit($pager->firstRow.','.$pager->listRows)->select();
        foreach ($list as &$l){
        	if($l['images']){
        		$l['images'] =  explode(',',$l['images']);;
        	}
        }
        
        $this->assign('list',$list);
        $this->assign('page',$pager->show());

        $this->assign('list_table', true);

        $this->display();
    }
    
    /**
     * 删除
     */
    public function delete()
    {
        $ids = trim($this->_request('id'), ',');
        if ($ids) {
            $item_ids = $this->_mod->where(array('id'=>array('in', $ids)))->getField('item_id', true);
            if (false !== $this->_mod->delete($ids)) {
                $item_mod = D('item');
                foreach ($item_ids as $item_id) {
                    $item_mod->update_comments($item_id);
                }
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'));
            } else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
            }
        } else {
            IS_AJAX && $this->ajaxReturn(0, L('illegal_parameters'));
        }
    }
    
    public function edit(){
    	if (IS_POST) {
    		$id = $this->_post ( 'id', 'trim' );
    		$reinfo = $this->_post ( 'reinfo', 'trim' );
    		$savedata['re_time'] = time();
    		$savedata['reinfo'] = $reinfo;
    		$res = $this->_mod->where(array('id'=>$id))->save($savedata);
    		if (false !== $res) {
    			$this->success(L('operation_success'), U('item_comment/index'));
    		} else {
    			$this->error(L('operation_failure'));
    		}
    	} else {
    		$id = $this->_get('id', 'intval');
    		if(empty($id)){
    			$this->error(L('illegal_parameters'));
    		}
    		$prefix = C(DB_PREFIX);
    		$info = $this->_mod->field($prefix.'item_comment.*,'.$prefix.'user.username,'.$prefix.'item_base.title as item_name,'.$prefix.'item_base.img')
    					->join($prefix.'user ON '.$prefix.'user.id='.$prefix.'item_comment.userid')
    					->join($prefix.'item ON '.$prefix.'item.id='.$prefix.'item_comment.item_id')
    					->join($prefix.'item_base ON '.$prefix.'item.baseid='.$prefix.'item_base.id')
    					->where($prefix.'item_comment.id='.$id)->find();
    		if($info['images']){
    			$info['images'] =  explode(',',$info['images']);;
    		}
    		if(empty($info)){
    			$this->error(L('illegal_parameters'));
    		}
    		$this->assign('info', $info);
    		$this->display();
    	}
    }
}