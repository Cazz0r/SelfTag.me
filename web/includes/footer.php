		</div>
	</main>
	<footer class="text-muted">
		<div class="container">
			<p class="float-right">
				<a href="#">Back to top</a>
			</p>

		</div>
	</footer>
	<!-- Modal -->
	<div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modaltitle" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="modaltitle">Modal title</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					Hello World
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary">Save changes</button>
				</div>
			</div>
		</div>
	</div>
	<script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>

	<!-- Popper.js for Tooltips: https://cdnjs.com/libraries/popper.js -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js"></script>

	<script language="javascript">
		$(document).ready(function(){
			
			//User page
			$('#User-Save').click(function(){
				var formdata = $(this).parent('form').serialize();
				CheckQueue(formdata);
			});
			var timeout; 
			function CheckQueue(formdata){
				$.post('/user/' + $("#guild").val() + '/' + $("#user").val() + '/' + $("#secret").val(), formdata,  function(data){
					var result = jQuery.parseJSON(data);
					if($('#modal').find('.modal-title').html() != result.title) $('#modal').find('.modal-title').html(result.title);
					if($('#modal').find('.modal-body').html() != result.body) $('#modal').find('.modal-body').html(result.body);
					if($('#modal').find('.modal-footer').html() != result.footer) $('#modal').find('.modal-footer').html(result.footer);
					var size = result.size || '';
					$('#modal').find('.modal-dialog').removeClass('modal-sm modal-lg modal-xl').addClass(size);
					
					if(result.hasOwnProperty('closable')){
						if(result.closeable){
							$('#modal').find('.modal-header').find('.close').attr('disabled', 'disabled');
						}else{
							$('#modal').find('.modal-header').find('.close').removeAttr('disabled');
						}
					}
					
					$('#modal').modal('show');
					
					if(result.hasOwnProperty('repeat')){
						timeout = setTimeout(function(){
							CheckQueue({action: "check"})
						}, 2500);
					}
					if(result.hasOwnProperty('roles')){
						$.each(result.roles, function(k, v){
							if(v){
								$('#Role-' + k).attr("checked", "checked");
							}else{
								$('#Role-' + k).removeAttr("checked");
							}
						});
					}
				});
			}
			$('#modal').on('hide.bs.modal', function (e) {
				clearTimeout(timeout);
			});
			
			//Guild page		
			function GuildBindings(){
				$(".Category-Maintenance").off().click(function(){
					CategoryMaintenance({action: 'categories'});
				});
				
				$("#insert-category-form").off().submit(function(e){
					e.preventDefault();
				});
				$('#insert-category').off().click(function(){
					var name = $('#insert-category-input').val();
					CategoryMaintenance({action: 'insert-category', 'name': name});
				});
				$('.remove-category').off().click(function(){
					var name = $(this).attr('data-name'), conf = confirm("Are you sure you wish to remove this category?");
					if(conf){
						CategoryMaintenance({action: 'remove-category', 'name': name});
					}
				});
				$('.move-category').off().click(function(){
					var name = $(this).attr('data-name'), direction = $(this).attr('data-dir');
					CategoryMaintenance({action: 'move-category', 'name': name, 'direction': direction});
				});
				$('.save-categories').off().click(function(){
					data = "action=save-categories&" + $("#category-form").serialize();
					CategoryMaintenance(data);
				});
				
				$(".Guild-Save").off().click(function(){
					data = "action=save-guild&" + $("#guild-form").serialize();
					CategoryMaintenance(data);
				});
				
			}
			function CategoryMaintenance(formdata){
				$.post('/guild/' + $("#guild").val() + '/' + $("#secret").val(), formdata,  function(data){
					var result = jQuery.parseJSON(data);
					if($('#modal').find('.modal-title').html() != result.title) $('#modal').find('.modal-title').html(result.title);
					if($('#modal').find('.modal-body').html() != result.body) $('#modal').find('.modal-body').html(result.body);
					if($('#modal').find('.modal-footer').html() != result.footer) $('#modal').find('.modal-footer').html(result.footer);
					var size = result.size || '';
					$('#modal').find('.modal-dialog').removeClass('modal-sm modal-lg modal-xl').addClass(size);
					
					if(result.hasOwnProperty('closable')){
						if(result.closeable){
							$('#modal').find('.modal-header').find('.close').attr('disabled', 'disabled');
						}else{
							$('#modal').find('.modal-header').find('.close').removeAttr('disabled');
						}
					}
					
					$('#modal').modal('show');
					
					if(result.hasOwnProperty('categories')){
						$.each(result.categories, function(k, v){
							
						});
					}
					
					GuildBindings();
				});
			}
			GuildBindings();
			
			
			//Universal
			$('[data-toggle="tooltip"]').tooltip();
			$("#modal").on('shown.bs.modal', function(e){
				$('[data-toggle="tooltip"]').tooltip();
			});
			
		});
	</script>
	<style>
	.tag {
		padding:2px;
		margin:2px;
		display:inline-block;
		font-weight: bold;
		border: 1px solid #000000;
	}
	</style>
</body>
</html>