import axios from 'axios';

export default async function hasPermission({ next, to }) {
    try {
        const { data } = await axios.get('/api/session/checkUserLoggedSesion');

        const user = data.userLogged;
        const userSubdomain = data.userSubdomain;

        // Cache
        localStorage.setItem('userLogged', JSON.stringify(user));
        localStorage.setItem('userSubdomain', JSON.stringify(userSubdomain));

        // Ruta sin permisos
        if (!to.meta.permissions?.length) {
            return next();
        }

        const label = user?.label;

        if (!label) {
            return next({ name: 'dashboard' });
        }

      

        const permissionsSource =
            label === 'Usuario subdominio'
                ? user.labels_permissions
                : userSubdomain?.labels_permissions;

        if (!permissionsSource) {
            return next({ name: 'dashboard' });
        }

        const labelPermissions = permissionsSource[label];

        if (!labelPermissions) {
            return next({ name: 'dashboard' });
        }


        const allowed = to.meta.permissions.every(p => {
            const [module, action] = p.split('.');
            return labelPermissions?.[module]?.includes(action);
        });

        return allowed
            ? next()
            : next({ name: 'dashboard' });

    } catch (error) {
        window.location.href = '/portal';
    }
}
