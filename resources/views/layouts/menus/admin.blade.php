<li class="u-sidebar-nav-menu__item">
	<a class="u-sidebar-nav-menu__link" href="{{ url('categories') }}">
		<i class="fa fa-boxes u-sidebar-nav-menu__item-icon"></i>
		<span class="u-sidebar-nav-menu__item-title">{{ _lang('Categories') }}</span>
	</a>
</li>
<li class="u-sidebar-nav-menu__item">
	<a class="u-sidebar-nav-menu__link" href="#!" data-target="#pos-menu">
		<i class="fa fa-calculator u-sidebar-nav-menu__item-icon"></i>
		<span class="u-sidebar-nav-menu__item-title">{{ _lang('POS System') }}</span>
		<i class="fa fa-angle-right u-sidebar-nav-menu__item-arrow"></i>
		<span class="u-sidebar-nav-menu__indicator"></span>
	</a>

	<ul id="pos-menu" class="u-sidebar-nav-menu u-sidebar-nav-menu--second-level" style="display: none;">
		<li class="u-sidebar-nav-menu__item">
			<a class="u-sidebar-nav-menu__link" href="{{ url('sales') }}">
				<span class="u-sidebar-nav-menu__item-icon fa fa-angle-right"></span>
				<span class="u-sidebar-nav-menu__item-title">{{ _lang('Sales') }}</span>
			</a>
		</li>
		<li class="u-sidebar-nav-menu__item">
			<a class="u-sidebar-nav-menu__link" href="{{ url('payments') }}">
				<span class="u-sidebar-nav-menu__item-icon fa fa-angle-right"></span>
				<span class="u-sidebar-nav-menu__item-title">{{ _lang('Payments') }}</span>
			</a>
		</li>
		<li class="u-sidebar-nav-menu__item">
			<a class="u-sidebar-nav-menu__link" href="{{ url('items') }}">
				<span class="u-sidebar-nav-menu__item-icon fa fa-angle-right"></span>
				<span class="u-sidebar-nav-menu__item-title">{{ _lang('Items') }}</span>
			</a>
		</li>
		<li class="u-sidebar-nav-menu__item">
			<a class="u-sidebar-nav-menu__link" href="{{ url('ingredients') }}">
				<span class="u-sidebar-nav-menu__item-icon fa fa-angle-right"></span>
				<span class="u-sidebar-nav-menu__item-title">{{ _lang('Ingredients') }}</span>
			</a>
		</li>
		<li class="u-sidebar-nav-menu__item">
			<a class="u-sidebar-nav-menu__link" href="{{ url('stocks') }}">
				<span class="u-sidebar-nav-menu__item-icon fa fa-angle-right"></span>
				<span class="u-sidebar-nav-menu__item-title">{{ _lang('Stock Management') }}</span>
			</a>
		</li>
		<li class="u-sidebar-nav-menu__item">
			<a class="u-sidebar-nav-menu__link" href="{{ url('expenses') }}">
				<span class="u-sidebar-nav-menu__item-icon fa fa-angle-right"></span>
				<span class="u-sidebar-nav-menu__item-title">{{ _lang('Expenses') }}</span>
			</a>
		</li>
		<li class="u-sidebar-nav-menu__item">
			<a class="u-sidebar-nav-menu__link" href="{{ url('reports') }}">
				<span class="u-sidebar-nav-menu__item-icon fa fa-angle-right"></span>
				<span class="u-sidebar-nav-menu__item-title">{{ _lang('Reports') }}</span>
			</a>
		</li>
	</ul>
</li>
<li class="u-sidebar-nav-menu__item">
	<a class="u-sidebar-nav-menu__link" href="{{ url('notifications') }}">
		<i class="fa fa-bell u-sidebar-nav-menu__item-icon"></i>
		<span class="u-sidebar-nav-menu__item-title">{{ _lang('Notifications') }}</span>
	</a>
</li>
<li class="u-sidebar-nav-menu__item">
	<a class="u-sidebar-nav-menu__link" href="{{ url('users') }}">
		<i class="fa fa-users u-sidebar-nav-menu__item-icon"></i>
		<span class="u-sidebar-nav-menu__item-title">{{ _lang('Manage Users') }}</span>
	</a>
</li>
<li class="u-sidebar-nav-menu__item">
	<a class="u-sidebar-nav-menu__link" href="{{ url('cache') }}">
		<i class="fas fa-trash u-sidebar-nav-menu__item-icon"></i>
		<span class="u-sidebar-nav-menu__item-title">{{ _lang('Cache Clear') }}</span>
	</a>
</li>
<li class="u-sidebar-nav-menu__item">
	<a class="u-sidebar-nav-menu__link" href="#!" data-target="#administration">
		<i class="far fa-folder-open u-sidebar-nav-menu__item-icon"></i>
		<span class="u-sidebar-nav-menu__item-title">{{ _lang('Administration') }}</span>
		<i class="fa fa-angle-right u-sidebar-nav-menu__item-arrow"></i>
		<span class="u-sidebar-nav-menu__indicator"></span>
	</a>

	<ul id="administration" class="u-sidebar-nav-menu u-sidebar-nav-menu--second-level" style="display: none;">
		<li class="u-sidebar-nav-menu__item">
			<a class="u-sidebar-nav-menu__link" href="{{ url('system_users') }}">
				<span class="u-sidebar-nav-menu__item-icon fa fa-angle-right"></span>
				<span class="u-sidebar-nav-menu__item-title">{{ _lang('Syatem Users') }}</span>
			</a>
		</li>
		<li class="u-sidebar-nav-menu__item">
			<a class="u-sidebar-nav-menu__link" href="{{ url('app_settings') }}">
				<span class="u-sidebar-nav-menu__item-icon fa fa-angle-right"></span>
				<span class="u-sidebar-nav-menu__item-title">{{ _lang('App Settings') }}</span>
			</a>
		</li>
		<li class="u-sidebar-nav-menu__item">
			<a class="u-sidebar-nav-menu__link" href="{{ url('general_settings') }}">
				<span class="u-sidebar-nav-menu__item-icon fa fa-angle-right"></span>
				<span class="u-sidebar-nav-menu__item-title">{{ _lang('General Settings') }}</span>
			</a>
		</li>
		
	</ul>
</li>
