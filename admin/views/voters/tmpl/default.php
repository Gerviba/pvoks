<?php

defined("_JEXEC") or die("Restricted access");

// necessary libraries
$input = Jfactory::getApplication()->input;
$db = JFactory::getDBO();
$listOrder = $input->get('filter_order');
$listDirn = $input->get('filter_order_Dir');
$pvoksHelper = new PvoksHelper();
$pvoksHelper->echoControlPanel();
?>

<h2><i class="icon-user"></i><?php echo $this->title; ?></h2>
<div style="clear:both"><br /></div>
<div class="pvoksForm">
<form action="<?php echo JRoute::_('index.php?option=com_pvoks&view=categories'); ?>" method="get" name="adminForm" id="adminForm">
    <div class="filterForm">
	  <div class="control-group">
	   <label><?php echo JText::_('PVOKS_FILTER'); ?></label>
	   <input type="text" class="filterStr" size="40" name="filter_str" value="<?php echo $input->get('filter_str','','string'); ?>" />
	  </div>
	  <div class="control-group">
	   <label><?php echo JText::_('PVOKS_QUESTION_ID'); ?>:</label>
	   <select name="filter_question_id" style="width:330px; max-width:330px;">
	     <option value="0"<?php if($input->get('filter_question_id',0)==0) echo ' selected="selected"'; ?>><?php echo JText::_('PVOKS_ALL'); ?></option>
		 <?php 
		 $db->setQuery('select id,title from #__pvoks_questions order by 2');
		 $res = $db->loadObjectList();
		 foreach ($res as $res1) :
		 ?>
	     <option value="<?php echo $res1->id; ?>"<?php if ($input->get('filter_question_id') == $res1->id) echo ' selected="selected"'; ?>><?php echo $res1->title; ?></option>
	     <?php endforeach; ?>
	   </select>
	  </div>
	  <div class="control-buttons">
	   <button type="submit" name="newfilter" value="1" class="btn hasTooltip" title="<?php echo JText::_('PVOKS_FILTER_START'); ?>"><i class="icon-search"></i></button>
	   <button type="submit" name="clrfilter" value="1" class="btn hasTooltip" 
	     onclick="var f=document.forms.adminForm; f.filter_str.value=''; f.filter_category_id.value=0; f.filter_acredited_id.value = 0; f.filter_user_id.value=0; true;" 
		 title="<?php echo JText::_('PVOKS_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
	  </div>	 
	</div>
	
	<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items"><?php echo JText::_('PVOKS_NO_DATA'); ?></div>
	<?php else : ?>
	<p><?php echo JText::_('PVOKS_SUMMA').': '.$this->total; ?> adat</p>
	<table class="table table-striped" id="articleList" class="adminList" style="width:100%">
		<thead>
			<tr>
				<!-- item checkbox -->
				<th></th>
				<th class="nowrap left">
					<?php $pvoksHelper->echoColHeader('ID','a.id', $listDirn, $listOrder); ?>
				</th>
				<th class="nowrap left">
					<?php $pvoksHelper->echoColHeader('PVOKS_CATEGORY_ID','a.category_id', $listDirn, $listOrder); ?>
				</th>
				<th class="nowrap left">
					<?php $pvoksHelper->echoColHeader('PVOKS_MEMBERS_NAME','u.name', $listDirn, $listOrder); ?>
				</th>
				<th>
					<?php $pvoksHelper->echoColHeader('PVOKS_CREATED','a.created', $listDirn, $listOrder); ?>
				</th>
			</tr>
		</thead>
				
		<tbody>
		
		<?php foreach ($this->items as $i => $item) :
			$link = ''; 
		?>
		
			<tr class="row<?php echo $i % 2; ?>">
				
				<!-- item checkbox -->
				<td class="center"><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>
				<!-- item main field -->
				<td>
					<a href="#" title="<?php echo JText::_('PVOKS_OPEN'); ?>" 
					   onclick="itemClick(<?php echo $item->id; ?>); return false;">
					   <i class="icon-edit"></i></a><?php echo $this->escape($item->id); ?>
				</td>
				<td class="left"><?php echo $this->escape($item->title); ?></td>
				<td class="left"><?php echo $this->escape($item->name); ?></td>
				<td class="left"><?php echo $this->escape($item->created); ?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
		<tfoot>
		  <tr>
		    <td colspan="3">
				<p><?php echo JText::_('PVOKS_SUMMA').': '.$this->total; ?> adat</p>
			    <?php $limit = $input->get('limit',20); ?>
				<?php if ($input->get('filter_order') != 'a.tree') : ?>
				<select name="limit" onchange="document.adminForm.limitstart=0; Joomla.submitform();" style="width:auto">
				  <option value="5"<?php if ($limit==5) echo ' selected="selected"'; ?>>5</option>
				  <option value="10"<?php if ($limit==10) echo ' selected="selected"'; ?>>10</option>
				  <option value="15"<?php if ($limit==15) echo ' selected="selected"'; ?>>15</option>
				  <option value="20"<?php if ($limit==20) echo ' selected="selected"'; ?>>20</option>
				  <option value="30"<?php if ($limit==30) echo ' selected="selected"'; ?>>30</option>
				  <option value="40"<?php if ($limit==40) echo ' selected="selected"'; ?>>40</option>
				  <option value="50"<?php if ($limit==50) echo ' selected="selected"'; ?>>50</option>
				  <option value="100"<?php if ($limit==100) echo ' selected="selected"'; ?>>100</option>
				</select>
				adat/oldal
				<?php endif; ?>
			</td>
		    <td colspan="12">
				<?php echo $this->pagination->getListFooter(); ?>
			</td>
		  </tr>
		</tfoot>
	</table>
	<?php endif; ?>
	<input type="hidden" name="option" value="com_pvoks" />
	<input type="hidden" name="view" value="voters" />	
	<input type="hidden" name="task" value="browse" />
	<input type="hidden" name="id" value="" />
	<input type="hidden" name="boxchecked" value="0" /><!-- itt htm szinten ne legyen value! -->
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
	<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
<div style="clear:both"></div>
