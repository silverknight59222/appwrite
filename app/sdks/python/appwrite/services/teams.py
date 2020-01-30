from ..service import Service


class Teams(Service):

    def __init__(self, client):
        super(Teams, self).__init__(client)

    def list(self, search='', limit=25, offset=0, order_type='ASC'):
        """List Teams"""

        params = {}
        path = '/teams'
        params['search'] = search
        params['limit'] = limit
        params['offset'] = offset
        params['orderType'] = order_type

        return self.client.call('get', path, {
            'content-type': 'application/json',
        }, params)

    def create(self, name, roles=[]):
        """Create Team"""

        params = {}
        path = '/teams'
        params['name'] = name
        params['roles'] = roles

        return self.client.call('post', path, {
            'content-type': 'application/json',
        }, params)

    def get(self, team_id):
        """Get Team"""

        params = {}
        path = '/teams/{teamId}'
        path = path.replace('{teamId}', team_id)                

        return self.client.call('get', path, {
            'content-type': 'application/json',
        }, params)

    def update(self, team_id, name):
        """Update Team"""

        params = {}
        path = '/teams/{teamId}'
        path = path.replace('{teamId}', team_id)                
        params['name'] = name

        return self.client.call('put', path, {
            'content-type': 'application/json',
        }, params)

    def delete(self, team_id):
        """Delete Team"""

        params = {}
        path = '/teams/{teamId}'
        path = path.replace('{teamId}', team_id)                

        return self.client.call('delete', path, {
            'content-type': 'application/json',
        }, params)

    def get_memberships(self, team_id):
        """Get Team Memberships"""

        params = {}
        path = '/teams/{teamId}/memberships'
        path = path.replace('{teamId}', team_id)                

        return self.client.call('get', path, {
            'content-type': 'application/json',
        }, params)

    def create_membership(self, team_id, email, roles, url, name=''):
        """Create Team Membership"""

        params = {}
        path = '/teams/{teamId}/memberships'
        path = path.replace('{teamId}', team_id)                
        params['email'] = email
        params['name'] = name
        params['roles'] = roles
        params['url'] = url

        return self.client.call('post', path, {
            'content-type': 'application/json',
        }, params)

    def delete_membership(self, team_id, invite_id):
        """Delete Team Membership"""

        params = {}
        path = '/teams/{teamId}/memberships/{inviteId}'
        path = path.replace('{teamId}', team_id)                
        path = path.replace('{inviteId}', invite_id)                

        return self.client.call('delete', path, {
            'content-type': 'application/json',
        }, params)
